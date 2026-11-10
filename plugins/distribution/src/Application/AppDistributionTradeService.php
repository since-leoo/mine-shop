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

namespace Plugin\Distribution\Application;

use Hyperf\DbConnection\Db;
use Plugin\Distribution\Domain\Service\DomainCommissionService;

final class AppDistributionTradeService
{
    public function __construct(private readonly DomainCommissionService $commissions) {}

    public function snapshot(int $buyer, array $payload): array
    {
        return $this->commissions->snapshot($buyer, $payload);
    }

    public function created(string $no, array $snapshot): void
    {
        Db::transaction(fn () => $this->commissions->created($no, $snapshot));
    }

    public function paid(int $id): void
    {
        Db::transaction(fn () => $this->commissions->paid($id));
    }

    public function refunded(int $id): void
    {
        Db::transaction(fn () => $this->commissions->refund($id));
    }

    public function recordCompletion(int $id, ?string $completedAt = null): void
    {
        Db::transaction(function () use ($id, $completedAt): void {
            $this->commissions->recordCompletion($id, $completedAt);
            $this->commissions->settle($id);
        });
    }

    public function completed(int $id): void
    {
        Db::transaction(fn () => $this->commissions->settle($id));
    }

    public function settleDue(): array
    {
        $failures = [];
        foreach ($this->commissions->pendingOrders() as $id) {
            try {
                $this->completed($id);
            } catch (\Throwable $e) {
                $failures[] = ['order_id' => $id, 'error' => $e->getMessage()];
            }
        }
        return $failures;
    }
}
