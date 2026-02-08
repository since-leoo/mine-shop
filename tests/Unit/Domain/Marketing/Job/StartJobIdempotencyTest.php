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

namespace HyperfTests\Unit\Domain\Marketing\Job;

use App\Domain\Marketing\GroupBuy\Enum\GroupBuyStatus;
use App\Domain\Marketing\GroupBuy\Job\GroupBuyStartJob;
use App\Domain\Marketing\GroupBuy\Repository\GroupBuyRepository;
use App\Domain\Marketing\GroupBuy\Service\DomainGroupBuyService;
use App\Domain\Marketing\Seckill\Enum\SeckillStatus;
use App\Domain\Marketing\Seckill\Job\SeckillSessionStartJob;
use App\Domain\Marketing\Seckill\Repository\SeckillSessionRepository;
use App\Domain\Marketing\Seckill\Service\DomainSeckillActivityService;
use App\Domain\Marketing\Seckill\Service\DomainSeckillSessionService;
use Hyperf\Context\ApplicationContext;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Feature: marketing-activity-status, Property 2: Start Job 幂等性.
 *
 * For any 活动或场次，当其状态不是 pending 时，执行 StartJob 应为无操作（不抛异常、不修改状态）。
 *
 * **Validates: Requirements 1.3, 5.3**
 *
 * @internal
 * @coversNothing
 */
final class StartJobIdempotencyTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const ITERATIONS = 100;

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    // ========================================================================
    // GroupBuyStartJob 幂等性测试
    // ========================================================================

    /**
     * Property 2: GroupBuyStartJob 幂等性 — 非 pending 状态时跳过，不抛异常、不修改状态.
     *
     * For any GroupBuyActivity with status other than 'pending' (active, ended, cancelled, sold_out),
     * executing GroupBuyStartJob should be a no-op: no exception thrown, start() never called.
     *
     * **Validates: Requirements 5.3**
     *
     * @dataProvider provideGroupBuyStartJobSkipsNonPendingStatusCases
     */
    public function testGroupBuyStartJobSkipsNonPendingStatus(string $status, int $groupBuyId): void
    {
        // Arrange: mock the group buy model with the given non-pending status
        $mockGroupBuy = new \stdClass();
        $mockGroupBuy->status = $status;
        $mockGroupBuy->id = $groupBuyId;

        $mockRepository = \Mockery::mock(GroupBuyRepository::class);
        $mockRepository->shouldReceive('findById')
            ->with($groupBuyId)
            ->andReturn($mockGroupBuy);

        $mockService = \Mockery::mock(DomainGroupBuyService::class);
        $mockService->repository = $mockRepository;
        // start() should NEVER be called for non-pending statuses
        $mockService->shouldNotReceive('start');

        $mockLogger = \Mockery::mock(LoggerInterface::class);
        $mockLogger->shouldReceive('info')->zeroOrMoreTimes();
        $mockLogger->shouldReceive('warning')->zeroOrMoreTimes();

        $mockContainer = \Mockery::mock(ContainerInterface::class);
        $mockContainer->shouldReceive('get')
            ->with(DomainGroupBuyService::class)
            ->andReturn($mockService);
        $mockContainer->shouldReceive('get')
            ->with(LoggerInterface::class)
            ->andReturn($mockLogger);

        ApplicationContext::setContainer($mockContainer);

        // Act: execute the job — should not throw
        $job = new GroupBuyStartJob($groupBuyId);
        $job->handle();

        // Assert: if we reach here, no exception was thrown (idempotent behavior confirmed)
        self::assertTrue(true, \sprintf(
            'GroupBuyStartJob should skip without error for status "%s" (id=%d)',
            $status,
            $groupBuyId,
        ));
    }

    // ========================================================================
    // Data Providers — generating at least 100 data sets each
    // ========================================================================

    /**
     * Generates 100+ non-pending GroupBuy statuses with random IDs.
     * Distributes evenly across active, ended, cancelled, sold_out (25 each).
     *
     * @return \Generator<string, array{string, int}>
     */
    public static function provideGroupBuyStartJobSkipsNonPendingStatusCases(): iterable
    {
        $nonPendingStatuses = [
            GroupBuyStatus::ACTIVE->value,
            GroupBuyStatus::ENDED->value,
            GroupBuyStatus::CANCELLED->value,
            GroupBuyStatus::SOLD_OUT->value,
        ];
        $perStatus = (int) ceil(self::ITERATIONS / \count($nonPendingStatuses));

        foreach ($nonPendingStatuses as $status) {
            for ($i = 0; $i < $perStatus; ++$i) {
                $groupBuyId = random_int(1, 100000);
                yield "GroupBuy {$status} #{$i}" => [$status, $groupBuyId];
            }
        }
    }

    /**
     * Property 2: GroupBuyStartJob 幂等性 — 活动不存在时跳过，不抛异常.
     *
     * **Validates: Requirements 5.3**
     *
     * @dataProvider provideGroupBuyStartJobSkipsWhenActivityNotFoundCases
     */
    public function testGroupBuyStartJobSkipsWhenActivityNotFound(int $groupBuyId): void
    {
        $mockRepository = \Mockery::mock(GroupBuyRepository::class);
        $mockRepository->shouldReceive('findById')
            ->with($groupBuyId)
            ->andReturn(null);

        $mockService = \Mockery::mock(DomainGroupBuyService::class);
        $mockService->repository = $mockRepository;
        $mockService->shouldNotReceive('start');

        $mockLogger = \Mockery::mock(LoggerInterface::class);
        $mockLogger->shouldReceive('info')->zeroOrMoreTimes();
        $mockLogger->shouldReceive('warning')->zeroOrMoreTimes();

        $mockContainer = \Mockery::mock(ContainerInterface::class);
        $mockContainer->shouldReceive('get')
            ->with(DomainGroupBuyService::class)
            ->andReturn($mockService);
        $mockContainer->shouldReceive('get')
            ->with(LoggerInterface::class)
            ->andReturn($mockLogger);

        ApplicationContext::setContainer($mockContainer);

        $job = new GroupBuyStartJob($groupBuyId);
        $job->handle();

        self::assertTrue(true, 'GroupBuyStartJob should skip without error when activity not found');
    }

    /**
     * Generates 100 random non-existent group buy IDs.
     *
     * @return \Generator<string, array{int}>
     */
    public static function provideGroupBuyStartJobSkipsWhenActivityNotFoundCases(): iterable
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            yield "non-existent GroupBuy #{$i}" => [random_int(1, 100000)];
        }
    }

    // ========================================================================
    // SeckillSessionStartJob 幂等性测试
    // ========================================================================

    /**
     * Property 2: SeckillSessionStartJob 幂等性 — 非 pending 状态时跳过，不抛异常、不修改状态.
     *
     * For any SeckillSession with status other than 'pending' (active, ended, cancelled, sold_out),
     * executing SeckillSessionStartJob should be a no-op: no exception thrown, start() never called.
     *
     * **Validates: Requirements 1.3**
     *
     * @dataProvider provideSeckillSessionStartJobSkipsNonPendingStatusCases
     */
    public function testSeckillSessionStartJobSkipsNonPendingStatus(string $status, int $sessionId, int $activityId): void
    {
        // Arrange: mock the session model with the given non-pending status
        $mockSession = new \stdClass();
        $mockSession->status = $status;
        $mockSession->id = $sessionId;

        $mockSessionRepository = \Mockery::mock(SeckillSessionRepository::class);
        $mockSessionRepository->shouldReceive('findById')
            ->with($sessionId)
            ->andReturn($mockSession);

        $mockSessionService = \Mockery::mock(DomainSeckillSessionService::class);
        $mockSessionService->repository = $mockSessionRepository;
        // start() should NEVER be called for non-pending statuses
        $mockSessionService->shouldNotReceive('start');

        $mockActivityService = \Mockery::mock(DomainSeckillActivityService::class);
        // Activity service should not be involved at all
        $mockActivityService->shouldNotReceive('start');

        $mockLogger = \Mockery::mock(LoggerInterface::class);
        $mockLogger->shouldReceive('info')->zeroOrMoreTimes();
        $mockLogger->shouldReceive('warning')->zeroOrMoreTimes();

        $mockContainer = \Mockery::mock(ContainerInterface::class);
        $mockContainer->shouldReceive('get')
            ->with(DomainSeckillSessionService::class)
            ->andReturn($mockSessionService);
        $mockContainer->shouldReceive('get')
            ->with(DomainSeckillActivityService::class)
            ->andReturn($mockActivityService);
        $mockContainer->shouldReceive('get')
            ->with(LoggerInterface::class)
            ->andReturn($mockLogger);

        ApplicationContext::setContainer($mockContainer);

        // Act: execute the job — should not throw
        $job = new SeckillSessionStartJob($sessionId, $activityId);
        $job->handle();

        // Assert: if we reach here, no exception was thrown (idempotent behavior confirmed)
        self::assertTrue(true, \sprintf(
            'SeckillSessionStartJob should skip without error for status "%s" (sessionId=%d, activityId=%d)',
            $status,
            $sessionId,
            $activityId,
        ));
    }

    /**
     * Generates 100+ non-pending Seckill statuses with random session/activity IDs.
     * Distributes evenly across active, ended, cancelled, sold_out (25 each).
     *
     * @return \Generator<string, array{string, int, int}>
     */
    public static function provideSeckillSessionStartJobSkipsNonPendingStatusCases(): iterable
    {
        $nonPendingStatuses = [
            SeckillStatus::ACTIVE->value,
            SeckillStatus::ENDED->value,
            SeckillStatus::CANCELLED->value,
            SeckillStatus::SOLD_OUT->value,
        ];
        $perStatus = (int) ceil(self::ITERATIONS / \count($nonPendingStatuses));

        foreach ($nonPendingStatuses as $status) {
            for ($i = 0; $i < $perStatus; ++$i) {
                $sessionId = random_int(1, 100000);
                $activityId = random_int(1, 100000);
                yield "Seckill {$status} #{$i}" => [$status, $sessionId, $activityId];
            }
        }
    }

    /**
     * Property 2: SeckillSessionStartJob 幂等性 — 场次不存在时跳过，不抛异常.
     *
     * **Validates: Requirements 1.3**
     *
     * @dataProvider provideSeckillSessionStartJobSkipsWhenSessionNotFoundCases
     */
    public function testSeckillSessionStartJobSkipsWhenSessionNotFound(int $sessionId, int $activityId): void
    {
        $mockSessionRepository = \Mockery::mock(SeckillSessionRepository::class);
        $mockSessionRepository->shouldReceive('findById')
            ->with($sessionId)
            ->andReturn(null);

        $mockSessionService = \Mockery::mock(DomainSeckillSessionService::class);
        $mockSessionService->repository = $mockSessionRepository;
        $mockSessionService->shouldNotReceive('start');

        $mockActivityService = \Mockery::mock(DomainSeckillActivityService::class);
        $mockActivityService->shouldNotReceive('start');

        $mockLogger = \Mockery::mock(LoggerInterface::class);
        $mockLogger->shouldReceive('info')->zeroOrMoreTimes();
        $mockLogger->shouldReceive('warning')->zeroOrMoreTimes();

        $mockContainer = \Mockery::mock(ContainerInterface::class);
        $mockContainer->shouldReceive('get')
            ->with(DomainSeckillSessionService::class)
            ->andReturn($mockSessionService);
        $mockContainer->shouldReceive('get')
            ->with(DomainSeckillActivityService::class)
            ->andReturn($mockActivityService);
        $mockContainer->shouldReceive('get')
            ->with(LoggerInterface::class)
            ->andReturn($mockLogger);

        ApplicationContext::setContainer($mockContainer);

        $job = new SeckillSessionStartJob($sessionId, $activityId);
        $job->handle();

        self::assertTrue(true, 'SeckillSessionStartJob should skip without error when session not found');
    }

    /**
     * Generates 100 random non-existent seckill session IDs.
     *
     * @return \Generator<string, array{int, int}>
     */
    public static function provideSeckillSessionStartJobSkipsWhenSessionNotFoundCases(): iterable
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $sessionId = random_int(1, 100000);
            $activityId = random_int(1, 100000);
            yield "non-existent SeckillSession #{$i}" => [$sessionId, $activityId];
        }
    }
}
