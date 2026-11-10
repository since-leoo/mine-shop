<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace HyperfTests\Unit\SupplyChain;

use PHPUnit\Framework\TestCase;
use Plugin\SupplyChain\Domain\Mapper\TaskMapper;
use Plugin\SupplyChain\Domain\ValueObject\TaskSpecVo;

/**
 * @internal
 * @coversNothing
 */
final class TaskLifecycleTest extends TestCase
{
    public function testRestoringTaskDoesNotMarkPersistedFieldsDirty(): void
    {
        $entity = TaskMapper::fromModel($this->pending());
        self::assertSame([], $entity->toArray());
        self::assertSame('pending', $entity->snapshot()['status']);
    }

    public function testDuplicateDeliveryAndWrongVersionDoNotAcquireAnotherLease(): void
    {
        $entity = TaskMapper::fromModel($this->pending());
        self::assertNull($entity->claim(99, time()));
        self::assertSame([], $entity->toArray());
        $token = $entity->claim(0, time());
        self::assertIsString($token);
        self::assertSame(32, mb_strlen($token));
        self::assertSame(1, $entity->snapshot()['attempt_count']);
        self::assertNull($entity->claim(0, time()));
        self::assertSame($token, $entity->snapshot()['lease_token']);
        self::assertSame(1, $entity->snapshot()['attempt_count']);
    }

    public function testDelayedTaskCannotBeClaimedBeforeDueTime(): void
    {
        $now = time();
        $entity = TaskMapper::fromModel(array_replace($this->pending(), ['next_retry_at' => date('Y-m-d H:i:s', $now + 60)]));
        self::assertNull($entity->claim(0, $now + 59));
        self::assertSame([], $entity->toArray());
        self::assertNotNull($entity->claim(0, $now + 60));
    }

    public function testLateResultFromPreviousLeaseCannotCompleteOrFailCurrentAttempt(): void
    {
        $now = time();
        $first = TaskMapper::fromModel($this->pending());
        $oldToken = $first->claim(0, $now);
        self::assertTrue($first->fail($oldToken, '暂时不可用', false, false, $now));
        $recovered = TaskMapper::fromModel($first->snapshot());
        $newToken = $recovered->claim(1, $now + 60);
        self::assertIsString($newToken);
        self::assertNotSame($oldToken, $newToken);
        $current = TaskMapper::fromModel($recovered->snapshot());
        self::assertFalse($current->complete($oldToken, ['ignored' => true]));
        self::assertFalse($current->fail($oldToken, '迟到错误', false, false, $now + 60));
        self::assertSame([], $current->toArray());
        self::assertSame('running', $current->snapshot()['status']);
        self::assertTrue($current->complete($newToken, ['imported' => 3]));
        self::assertSame('succeeded', $current->snapshot()['status']);
        self::assertNull($current->snapshot()['lease_token']);
        self::assertNull($current->snapshot()['next_retry_at']);
    }

    public function testExpiredWorkerCannotPublishSuccessBeforeRecoveryScannerRuns(): void
    {
        $now = time();
        $entity = TaskMapper::fromModel(array_replace($this->pending(), ['next_retry_at' => date('Y-m-d H:i:s', $now - 300)]));
        $token = $entity->claim(0, $now - 181);
        self::assertIsString($token);
        $expired = TaskMapper::fromModel($entity->snapshot());
        self::assertFalse($expired->complete($token, ['remote_order' => 'late']));
        self::assertSame([], $expired->toArray(), 'Recovery owns the expired task; a stale writer must not overwrite it');
    }

    public function testRetryableFailureSchedulesBackoffAndInvalidatesOldQueueMessage(): void
    {
        $now = time();
        $entity = TaskMapper::fromModel($this->pending());
        $token = $entity->claim(0, $now);
        self::assertTrue($entity->fail($token, '网络暂不可用', false, false, $now));
        $state = $entity->snapshot();
        self::assertSame('retry_wait', $state['status']);
        self::assertSame(1, $state['dispatch_version']);
        self::assertGreaterThanOrEqual($now + 30, strtotime($state['next_retry_at']));
        self::assertLessThanOrEqual($now + 45, strtotime($state['next_retry_at']));
        self::assertNull($state['lease_token']);
        self::assertNull($state['last_dispatched_at']);
        self::assertNull($entity->claim(0, $now + 60));
        self::assertNotNull($entity->claim(1, $now + 60));
    }

    public function testAttemptBudgetExhaustionStopsAutomaticRetries(): void
    {
        $now = time();
        $entity = TaskMapper::fromModel(array_replace($this->pending(), ['attempt_count' => 5, 'max_attempts' => 6]));
        $token = $entity->claim(0, $now);
        self::assertSame(6, $entity->snapshot()['attempt_count']);
        self::assertTrue($entity->fail($token, '已耗尽次数', false, false, $now));
        self::assertSame('failed', $entity->snapshot()['status']);
        self::assertNull($entity->snapshot()['next_retry_at']);
        self::assertNull($entity->claim(1, $now + 86400));
    }

    public function testRestoredExhaustedPendingTaskCannotBypassAttemptBudget(): void
    {
        $entity = TaskMapper::fromModel(array_replace($this->pending(), ['attempt_count' => 6, 'max_attempts' => 6]));
        self::assertNull($entity->claim(0, time()));
        self::assertSame(6, $entity->snapshot()['attempt_count']);
    }

    public function testUnknownMutationCannotBeAutomaticallyOrManuallyReplayed(): void
    {
        $now = time();
        $entity = TaskMapper::fromModel($this->pending());
        $token = $entity->claim(0, $now);
        self::assertTrue($entity->fail($token, '上游结果未知', true, true, $now));
        self::assertSame('unknown', $entity->snapshot()['status'], 'Uncertainty takes precedence over a generic permanent failure');
        self::assertNull($entity->snapshot()['next_retry_at']);
        self::assertNull($entity->claim(1, $now + 86400));
        $this->expectException(\DomainException::class);
        $entity->retry();
    }

    public function testManualRetryCreatesNewDispatchVersionWithoutErasingHistory(): void
    {
        $entity = TaskMapper::fromModel(array_replace($this->pending(), [
            'status' => 'failed', 'attempt_count' => 6, 'dispatch_version' => 6,
            'max_attempts' => 6, 'last_error' => '历史错误',
        ]));
        $trace = $entity->snapshot()['trace_id'];
        $entity->retry();
        self::assertSame('pending', $entity->snapshot()['status']);
        self::assertSame(7, $entity->snapshot()['dispatch_version']);
        self::assertSame(6, $entity->snapshot()['attempt_count']);
        self::assertSame(9, $entity->snapshot()['max_attempts']);
        self::assertSame($trace, $entity->snapshot()['trace_id']);
        self::assertSame('历史错误', $entity->snapshot()['last_error']);
        self::assertNull($entity->claim(6, time()));
        self::assertNotNull($entity->claim(7, time()));
    }

    public function testSucceededTaskCannotBeRetried(): void
    {
        $entity = TaskMapper::fromModel(array_replace($this->pending(), ['status' => 'succeeded']));
        $this->expectException(\DomainException::class);
        $entity->retry();
    }

    public function testInvalidConnectionCannotCreateTask(): void
    {
        $entity = TaskMapper::getNewEntity();
        $this->expectException(\InvalidArgumentException::class);
        $entity->create(new TaskSpecVo(0, 'order_push', 'test', []));
    }

    private function pending(): array
    {
        $entity = TaskMapper::getNewEntity();
        $entity->create(new TaskSpecVo(1, 'order_push', 'order:1:connection:1', ['purchase_id' => 1]));
        return $entity->snapshot();
    }
}
