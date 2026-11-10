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

namespace Plugin\SupplyChain\Application\Commad;

use App\Domain\Trade\Shared\Contract\TransactionManagerInterface;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Context\Context;
use Plugin\SupplyChain\Domain\Service\DomainSupplyTaskService;
use Plugin\SupplyChain\Domain\ValueObject\SafeLogVo;
use Plugin\SupplyChain\Infrastructure\Queue\SupplyTaskJob;
use Psr\Log\LoggerInterface;

final class AppSupplyTaskRunner
{
    public function __construct(private readonly DomainSupplyTaskService $tasks, private readonly AppSupplyWorkflow $workflow, private readonly TransactionManagerInterface $transactions, private readonly DriverFactory $queues, private readonly LoggerInterface $logger) {}

    public function run(int $id, int $version): void
    {
        $task = $this->transactions->run(fn () => $this->tasks->claim($id, $version));
        if ($task === null) {
            return;
        }
        Context::set('supply_chain.task_id', $id);
        Context::set('supply_chain.attempt_id', $task['attempt_id']);
        Context::set('supply_chain.trace_id', $task['trace_id']);
        try {
            $result = $this->workflow->execute($task);
            $this->transactions->run(fn () => $this->tasks->complete($task, $result));
        } catch (\Throwable $error) {
            // Persistence failures propagate: lease recovery discovers the unfinished attempt.
            $this->transactions->run(fn () => $this->tasks->fail($task, $error));
        } finally {
            foreach (['task_id', 'attempt_id', 'trace_id'] as $key) {
                Context::destroy('supply_chain.' . $key);
            }
        }
    }

    public function dispatch(): int
    {
        $this->transactions->run(fn () => $this->tasks->recoverExpired());
        $sent = 0;
        foreach ($this->tasks->due() as $task) {
            $queue = match (true) {
                str_starts_with($task['type'], 'order_') => 'supply_order',
                str_starts_with($task['type'], 'after_sale_') => 'supply_after_sale',
                \in_array($task['type'], ['messages', 'connection_refresh'], true) => 'supply_message',
                default => 'supply_catalog',
            };
            try {
                if (! $this->queues->get($queue)->push(new SupplyTaskJob((int) $task['id'], (int) $task['dispatch_version']))) {
                    throw new \RuntimeException('Redis队列拒绝投递');
                }
                $this->transactions->run(fn () => $this->tasks->dispatched((int) $task['id'], (int) $task['dispatch_version']));
                ++$sent;
            } catch (\Throwable $error) {
                $this->transactions->run(fn () => $this->tasks->event((int) $task['id'], 'dispatch_failed', ['reason' => SafeLogVo::error($error)]));
                $this->logger->error('供应链任务投递失败，数据库任务保留等待补发', ['task_id' => $task['id'], 'reason' => SafeLogVo::error($error)]);
            }
        }
        return $sent;
    }
}
