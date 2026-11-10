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

namespace Plugin\Distribution\Domain\Service;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use Plugin\Distribution\Domain\Mapper\CommissionAccountMapper;
use Plugin\Distribution\Domain\Repository\DistributionRepository;
use Plugin\Distribution\Domain\ValueObject\DistributionPolicyVo;

final class DomainCommissionService
{
    public function __construct(private readonly DistributionRepository $repository, private readonly DomainMallSettingService $mallSettings) {}

    public function snapshot(int $buyerId, array $payload): array
    {
        $version = $this->repository->settings();
        $s = $version['settings'];
        $buyer = $this->repository->member($buyerId);
        $parent = (int) ($buyer['referrer_id'] ?? 0);
        if (! $s['enabled'] || $parent === 0 || $parent === $buyerId) {
            return [];
        }
        $beneficiary = $this->repository->member($parent);
        if (! $beneficiary || ($beneficiary['status'] ?? 'active') !== 'active') {
            return [];
        }
        if (! $s['activity_enabled'] && ($payload['snapshot']['order_type'] ?? 'normal') !== 'normal') {
            return [];
        }
        $items = $payload['items'];
        $goods = max(0, (int) $payload['snapshot']['pay_amount'] - (int) $payload['snapshot']['shipping_fee']);
        $bases = DistributionPolicyVo::allocate($goods, array_map(static fn ($i) => (int) $i['total_price'], $items));
        $lines = [];
        foreach ($items as $i => $item) {
            $lines[] = ['product_id' => (int) $item['product_id'], 'sku_id' => (int) $item['sku_id'], 'quantity' => (int) $item['quantity'], 'base_amount' => $bases[$i], 'amount' => DistributionPolicyVo::commission($s, (int) $item['product_id'], (int) $item['sku_id'], (int) $item['quantity'], $bases[$i])];
        }
        if (array_sum(array_column($lines, 'amount')) === 0) {
            return [];
        }
        return ['buyer_id' => $buyerId, 'member_id' => $parent, 'rule_version' => $version['version'], 'settlement_days' => max((int) $s['settlement_days'], $this->mallSettings->order()->afterSaleDays()), 'commission_mode' => $s['commission_mode'], 'lines' => $lines];
    }

    public function created(string $orderNo, array $snapshot): void
    {
        if (! $snapshot) {
            return;
        }
        $order = $this->repository->orderByNo($orderNo);
        if (! $order || (int) $order['member_id'] !== (int) $snapshot['buyer_id']) {
            throw new \DomainException('分销订单归因不匹配');
        }
        $this->repository->createSnapshot((int) $order['id'], $snapshot);
    }

    public function paid(int $orderId): void
    {
        $initial = $this->repository->snapshot($orderId);
        if (! $initial) {
            return;
        }
        // Serialize a buyer's eligibility before locking either of their orders, so
        // out-of-order payment delivery cannot claim or deadlock the first-order slot.
        $this->repository->member((int) $initial['buyer_id'], true);
        $order = $this->repository->order($orderId, true);
        $record = $this->repository->snapshot($orderId, true);
        if (! $order || ! $record || $record['paid_at'] !== null || $order['pay_time'] === null) {
            return;
        }
        $snapshot = $record['snapshot'];
        $isFirst = $this->repository->claimFirst((int) $record['buyer_id'], $orderId);
        if ($snapshot['commission_mode'] === 'first_order' && ! $isFirst) {
            $this->repository->markPaid($orderId);
            return;
        }
        $items = $this->repository->items($orderId);
        $lines = $snapshot['lines'];
        if (\count($items) !== \count($lines)) {
            throw new \DomainException('订单商品与佣金快照不一致');
        }
        $account = CommissionAccountMapper::fromModel($this->repository->account((int) $record['member_id'], true));
        foreach ($items as $index => $item) {
            $line = $lines[$index];
            if ((int) $item['product_id'] !== $line['product_id'] || (int) $item['sku_id'] !== $line['sku_id']) {
                throw new \DomainException('商品归因快照顺序不一致');
            }
            if ($line['amount'] <= 0) {
                continue;
            }
            $id = $this->repository->createCommission(['order_id' => $orderId, 'order_item_id' => $item['id'], 'buyer_id' => $record['buyer_id'], 'member_id' => $record['member_id'], 'base_amount' => $line['base_amount'], 'amount' => $line['amount'], 'settlement_days' => $snapshot['settlement_days']]);
            $account->pending($line['amount']);
            $this->repository->saveAccount($account, 'commission:' . $id, 'commission', $line['amount']);
        }
        $this->repository->markPaid($orderId);
        // Refund notification may have arrived before payment outbox delivery.
        $this->refund($orderId);
    }

    public function refund(int $orderId): void
    {
        $order = $this->repository->order($orderId, true);
        if (! $order) {
            return;
        }
        $rows = $this->repository->commissionsForOrder($orderId);
        if (! $rows) {
            return;
        }
        $account = CommissionAccountMapper::fromModel($this->repository->account((int) $rows[0]['member_id'], true));
        $full = $this->repository->totalRefund($orderId) >= (int) $order['pay_amount'];
        foreach ($rows as $row) {
            $goods = $full ? (int) $row['base_amount'] : $this->repository->refundedGoods((int) $row['order_item_id']);
            $target = DistributionPolicyVo::refundCommission((int) $row['amount'], (int) $row['base_amount'], $goods);
            $delta = $target - (int) $row['refunded_amount'];
            if ($delta <= 0) {
                continue;
            }
            $account->reverse($delta, $row['status'] === 'settled');
            $this->repository->saveAccount($account, 'refund:' . $row['id'] . ':' . $target, 'refund', -$delta);
            $changes = ['refunded_amount' => $target];
            if ($target === (int) $row['amount'] && $row['status'] === 'pending') {
                $changes['status'] = 'cancelled';
            }
            $this->repository->updateCommission((int) $row['id'], $changes);
        }
    }

    public function recordCompletion(int $orderId, ?string $completedAt = null): void
    {
        $order = $this->repository->order($orderId, true);
        if ($order && $order['status'] === 'completed') {
            $this->repository->markCompleted($orderId, $completedAt ?? $this->repository->now());
        }
    }

    public function settle(int $orderId): void
    {
        $order = $this->repository->order($orderId, true);
        $snapshot = $this->repository->snapshot($orderId, true);
        if (! $order || $order['status'] !== 'completed' || empty($snapshot['completed_at']) || $this->repository->hasOpenAfterSale($orderId)) {
            return;
        }
        $this->refund($orderId);
        $rows = $this->repository->commissionsForOrder($orderId);
        if (! $rows) {
            return;
        }
        $account = CommissionAccountMapper::fromModel($this->repository->account((int) $rows[0]['member_id'], true));
        foreach ($rows as $row) {
            if ($row['status'] !== 'pending' || strtotime($snapshot['completed_at']) + (int) $row['settlement_days'] * 86400 > time()) {
                continue;
            }
            $net = (int) $row['amount'] - (int) $row['refunded_amount'];
            $account->settle($net);
            $this->repository->saveAccount($account, 'settle:' . $row['id'], 'settlement', $net);
            $this->repository->updateCommission((int) $row['id'], ['status' => 'settled', 'settled_at' => $this->repository->now()]);
        }
    }

    public function pendingOrders(): array
    {
        return $this->repository->pendingOrders();
    }
}
