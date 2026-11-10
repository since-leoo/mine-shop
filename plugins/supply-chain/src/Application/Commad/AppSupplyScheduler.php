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
use Hyperf\Redis\Redis;
use Plugin\SupplyChain\Domain\Service\DomainSupplyWorkflowService;

final class AppSupplyScheduler
{
    public function __construct(private readonly TransactionManagerInterface $transactions, private readonly DomainSupplyWorkflowService $workflow, private readonly AppSupplyTaskRunner $runner, private readonly Redis $redis) {}

    public function tick(): void
    {
        // Scheduling is local only. HTTP is performed exclusively by queued jobs.
        try {
            $this->transactions->run(fn () => $this->workflow->schedule());
            $cursor = (int) $this->redis->get('supply:recovery:paid_cursor');
            $result = $this->transactions->run(fn () => $this->workflow->paidRecoveryPage($cursor));
            $this->redis->set('supply:recovery:paid_cursor', $result['count'] < 100 ? 0 : $result['last_id']);
        } finally {
            // A malformed historical order must not prevent already durable tasks from running.
            $this->runner->dispatch();
        }
    }
}
