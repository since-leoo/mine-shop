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

namespace Plugin\SupplyChain\Domain\Service;

use App\Infrastructure\Error\SupplyChainError;
use Plugin\SupplyChain\Domain\Entity\TaskEntity;
use Plugin\SupplyChain\Domain\Mapper\PurchaseMapper;
use Plugin\SupplyChain\Domain\Mapper\SupplyAfterSaleMapper;
use Plugin\SupplyChain\Domain\Mapper\TaskMapper;
use Plugin\SupplyChain\Domain\Repository\SupplyRepository;
use Plugin\SupplyChain\Domain\ValueObject\SafeLogVo;
use Plugin\SupplyChain\Domain\ValueObject\TaskSpecVo;

final class DomainSupplyTaskService
{
    public function __construct(private readonly SupplyRepository $repository) {}

    public function getEntity(int $id, bool $lock = false): TaskEntity
    {
        return TaskMapper::fromModel($this->repository->require('tasks', $id, $lock));
    }

    /** Caller owns the local transaction. Unique business keys survive duplicate events. */
    public function enqueue(TaskSpecVo $spec): int
    {
        $entity = TaskMapper::getNewEntity();
        $entity->create($spec);
        $inserted = $this->repository->query('tasks')->insertOrIgnore($this->repository->encode($entity->toArray()));
        $id = (int) $this->repository->query('tasks')->where('business_key', $spec->businessKey)->value('id');
        if ($id < 1) {
            throw SupplyChainError::TASK_PERSISTENCE_FAILED->internal();
        }
        if ($inserted) {
            $this->event($id, 'created', ['type' => $spec->type], $spec->operatorId);
        }
        return $id;
    }

    public function claim(int $id, int $version): ?array
    {
        $row = $this->repository->require('tasks', $id, true);
        $entity = TaskMapper::fromModel($row);
        $token = $entity->claim($version, time());
        if ($token === null) {
            return null;
        }
        $this->repository->update('tasks', $id, $entity->toArray());
        $row = $entity->snapshot();
        $attemptId = $this->repository->insert('task_attempts', ['task_id' => $id, 'attempt_no' => $row['attempt_count'], 'lease_token' => $token, 'status' => 'running', 'parameters' => SafeLogVo::sanitize($row['payload']), 'started_at' => date('Y-m-d H:i:s')]);
        $this->event($id, 'claimed', ['attempt_id' => $attemptId]);
        return $row + ['attempt_id' => $attemptId];
    }

    public function complete(array $claimed, array $result): void
    {
        $entity = $this->getEntity((int) $claimed['id'], true);
        if (! $entity->complete($claimed['lease_token'], SafeLogVo::sanitize($result))) {
            $this->event((int) $claimed['id'], 'late_result', ['result' => SafeLogVo::sanitize($result)]);
            return;
        }
        $this->repository->update('tasks', (int) $claimed['id'], $entity->toArray());
        $this->repository->update('task_attempts', (int) $claimed['attempt_id'], ['status' => 'succeeded', 'result' => SafeLogVo::sanitize($result), 'finished_at' => date('Y-m-d H:i:s')]);
    }

    public function fail(array $claimed, \Throwable $error): void
    {
        $row = $this->repository->require('tasks', (int) $claimed['id'], true);
        $entity = TaskMapper::fromModel($row);
        // Any ambiguous mutating request is reconciled instead of automatically repeated.
        $uncertain = \in_array($row['type'], ['order_push', 'order_complete', 'order_cancel', 'after_sale_create', 'after_sale_cancel', 'after_sale_return'], true) && ! \in_array($error->getCode(), [429, 10002], true) && ! ($error instanceof \DomainException) && ! ($error instanceof \InvalidArgumentException);
        $reason = SafeLogVo::error($error);
        if (! $entity->fail($claimed['lease_token'], $reason, $uncertain, $error instanceof \DomainException || $error instanceof \InvalidArgumentException, time())) {
            return;
        }
        $this->repository->update('tasks', (int) $claimed['id'], $entity->toArray());
        $this->repository->update('task_attempts', (int) $claimed['attempt_id'], ['status' => $uncertain ? 'unknown' : 'failed', 'error_message' => $reason, 'finished_at' => date('Y-m-d H:i:s')]);
        $this->event((int) $claimed['id'], 'failed', ['reason' => $reason, 'next_status' => $entity->snapshot()['status']]);
        if ($uncertain && str_starts_with($row['type'], 'order_')) {
            $this->enqueue(new TaskSpecVo((int) $row['connection_id'], 'order_query', 'reconcile:' . $row['id'] . ':' . $row['attempt_count'], $row['payload']));
        }
        if ($uncertain && str_starts_with($row['type'], 'after_sale_')) {
            $this->enqueue(new TaskSpecVo((int) $row['connection_id'], 'after_sale_query', 'after-sale:' . $row['payload']['supply_after_sale_id'] . ':reconcile:' . $row['id'] . ':' . $row['attempt_count'], $row['payload']));
        }
    }

    public function retry(int $id, int $operator, string $reason): void
    {
        $entity = $this->getEntity($id, true);
        $entity->retry();
        $this->repository->update('tasks', $id, $entity->toArray());
        $this->event($id, 'manual_retry', ['reason' => $reason], $operator);
    }

    public function recoverExpired(): int
    {
        $rows = $this->repository->query('tasks')->where('status', 'running')->where('lease_expires_at', '<', date('Y-m-d H:i:s'))->limit(100)->lockForUpdate()->get();
        foreach ($rows as $raw) {
            $row = $this->repository->decode($raw);
            $attempt = $this->repository->query('task_attempts')->where('task_id', $row['id'])->where('lease_token', $row['lease_token'])->value('id');
            if ($attempt !== null) {
                $reason = '执行租约超时，原请求结果尚未确认';
                $this->recoverBusinessState($row, $reason);
                $this->fail($row + ['attempt_id' => $attempt], new \RuntimeException($reason));
            }
        }
        return \count($rows);
    }

    public function due(): array
    {
        return $this->repository->query('tasks')->whereIn('status', ['pending', 'retry_wait'])->where('next_retry_at', '<=', date('Y-m-d H:i:s'))
            ->where(static fn ($q) => $q->whereNull('last_dispatched_at')->orWhere('last_dispatched_at', '<', date('Y-m-d H:i:s', time() - 240)))
            ->orderBy('id')->limit(100)->get()->map(fn ($row) => $this->repository->decode($row))->all();
    }

    public function dispatched(int $id, int $version): void
    {
        $this->repository->query('tasks')->where('id', $id)->where('dispatch_version', $version)->whereIn('status', ['pending', 'retry_wait'])->update(['last_dispatched_at' => date('Y-m-d H:i:s')]);
        $this->event($id, 'dispatched', ['dispatch_version' => $version]);
    }

    public function event(int $id, string $action, array $detail, int $operator = 0): void
    {
        $this->repository->insert('task_events', ['task_id' => $id, 'action' => $action, 'detail' => SafeLogVo::sanitize($detail), 'operator_id' => $operator, 'created_at' => date('Y-m-d H:i:s')]);
    }

    /** Restore the business marker too: a killed worker cannot run its normal failure callback. */
    private function recoverBusinessState(array $task, string $reason): void
    {
        if ($task['type'] === 'order_push') {
            $id = (int) ($task['payload']['purchase_id'] ?? 0);
            $row = $this->repository->find('orders', $id, true);
            if ($row !== null && $row['status'] === 'submitting') {
                $entity = PurchaseMapper::fromModel($row);
                $entity->failed($reason, false);
                $this->repository->update('orders', $id, $entity->toArray());
            }
        }
        if (\in_array($task['type'], ['after_sale_create', 'after_sale_cancel', 'after_sale_return'], true)) {
            $id = (int) ($task['payload']['supply_after_sale_id'] ?? 0);
            $row = $this->repository->find('after_sales', $id, true);
            if ($row !== null && (int) ($row['snapshot']['revision'] ?? 0) === (int) ($task['payload']['revision'] ?? -1)) {
                $entity = SupplyAfterSaleMapper::fromModel($row);
                $entity->failed($task['type'], $reason, true, false);
                $this->repository->update('after_sales', $id, $entity->toArray());
            }
        }
    }
}
