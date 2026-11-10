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

namespace Plugin\Distribution\Interface\Transformer;

final class DistributionTransformer
{
    public function settings(array $row): array
    {
        return $row['settings'] + ['version' => (int) $row['version'], 'direct_limit' => 3, 'commission_levels' => 1];
    }

    public function overview(array $row): array
    {
        return $row + ['order_count' => $row['valid_order_count'] ?? 0, 'net_consumption_amount' => $row['net_amount'] ?? 0, 'pending_commission_amount' => $row['pending_commission'] ?? 0, 'settled_commission_amount' => 0, 'paid_withdrawal_amount' => 0];
    }

    public function page(array $page, string $type, bool $admin = false, bool $revealAccount = false): array
    {
        $page['list'] = array_map(fn (array $row) => $this->row($row, $type, $admin, $revealAccount), $page['list']);
        return $page;
    }

    public function row(array $row, string $type, bool $admin = false, bool $revealAccount = false): array
    {
        foreach (['id', 'member_id', 'order_id', 'order_item_id', 'buyer_id', 'depth', 'direct_count', 'team_count', 'paid_order_count', 'valid_order_count', 'net_amount', 'paid_amount', 'refund_amount', 'commission_amount', 'amount', 'base_amount', 'refunded_amount', 'fee_amount', 'net_amount', 'settlement_days'] as $key) {
            if (isset($row[$key])) {
                $row[$key] = (int) $row[$key];
            }
        }
        if ($type === 'member') {
            $row += ['member_id' => (int) $row['id'], 'parent_id' => isset($row['referrer_id']) ? (int) $row['referrer_id'] : null, 'depth' => 0, 'joined_at' => $row['created_at'], 'order_count' => $row['valid_order_count'], 'net_consumption_amount' => $row['net_amount']];
            if (! $admin) {
                unset($row['invite_code'],$row['referrer_id'],$row['parent_id']);
                $row['nickname'] = $this->mask($row['nickname'] ?? '');
            }
        }
        if ($type === 'order') {
            $row['member_nickname'] = $row['nickname'];
            if (! $admin) {
                $row['nickname'] = $this->mask($row['nickname'] ?? '');
                $row['member_nickname'] = $row['nickname'];
                $row['order_no'] = '…' . mb_substr($row['order_no'], -8);
            }
        }
        if ($type === 'commission') {
            $row['net_amount'] = $row['amount'] - $row['refunded_amount'];
            if (! $admin && isset($row['order_no'])) {
                $row['order_no'] = '…' . mb_substr($row['order_no'], -8);
            }
        }
        if ($type === 'withdrawal' && ! $revealAccount) {
            $row['account_name'] = $this->mask($row['account_name']);
            $row['account_no'] = '****' . mb_substr($row['account_no'], -4);
        }
        return $row;
    }

    private function mask(string $name): string
    {
        return $name === '' ? '用户' : mb_substr($name, 0, 1) . '***';
    }
}
