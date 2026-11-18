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

namespace Plugin\Distribution\Domain\Repository;

use App\Infrastructure\Error\DistributionError;
use Hyperf\DbConnection\Db;
use Plugin\Distribution\Domain\Entity\CommissionAccountEntity;
use Plugin\Distribution\Domain\ValueObject\DistributionPolicyVo;

class DistributionRepository
{
    public function settings(): array
    {
        $row = Db::table('distribution_rule_versions')->orderByDesc('id')->first();
        return ['version' => $row ? (int) $row->id : 0, 'settings' => $row ? array_replace(DistributionPolicyVo::defaults(), json_decode($row->settings, true, 512, \JSON_THROW_ON_ERROR)) : DistributionPolicyVo::defaults()];
    }

    public function saveSettings(array $settings, int $operator): int
    {
        $id = (int) Db::table('distribution_rule_versions')->insertGetId(['settings' => json_encode($settings, \JSON_THROW_ON_ERROR), 'operator_id' => $operator, 'created_at' => $this->now()]);
        $this->audit($operator, 'settings.update', $id, ['version' => $id]);
        return $id;
    }

    public function member(int $id, bool $lock = false): ?array
    {
        $q = Db::table('members')->where('id', $id);
        if ($lock) {
            $q->lockForUpdate();
        }
        $row = $q->first();
        return $row ? (array) $row : null;
    }

    public function byCode(string $code): ?array
    {
        $row = Db::table('members')->where('invite_code', $code)->first();
        return $row ? (array) $row : null;
    }

    public function directCount(int $id, bool $lock = false): int
    {
        $query = Db::table('members')->where('referrer_id', $id);
        return $lock ? $query->lockForUpdate()->pluck('id')->count() : $query->count();
    }

    public function binding(int $id): ?array
    {
        $row = Db::table('distribution_bindings')->where('member_id', $id)->first();
        return $row ? (array) $row : null;
    }

    public function bind(int $memberId, ?int $parent, ?int $attempt, string $reason, ?string $token): array
    {
        if ($parent !== null) {
            Db::table('members')->where('id', $memberId)->whereNull('referrer_id')->update(['referrer_id' => $parent]);
            Db::table('distribution_paths')->insertOrIgnore(['ancestor_id' => $parent, 'descendant_id' => $memberId, 'depth' => 1]);
            Db::insert('INSERT IGNORE INTO distribution_paths (ancestor_id,descendant_id,depth) SELECT ancestor_id,?,depth+1 FROM distribution_paths WHERE descendant_id=? AND ancestor_id<>?', [$memberId, $parent, $memberId]);
        }
        $row = ['member_id' => $memberId, 'referrer_id' => $parent, 'attempted_referrer_id' => $attempt, 'reason' => $reason, 'share_token' => $token, 'created_at' => $this->now()];
        Db::table('distribution_bindings')->insert($row);
        return $row;
    }

    public function share(string $token): ?array
    {
        $row = Db::table('distribution_shares')->where('token', $token)->where('expires_at', '>', $this->now())->first();
        if (! $row) {
            return null;
        }
        $data = (array) $row;
        $data['target'] = json_decode($data['target'], true, 512, \JSON_THROW_ON_ERROR);
        return $data;
    }

    public function createShare(int $memberId, string $channel, array $target): array
    {
        $token = bin2hex(random_bytes(12));
        $row = ['token' => $token, 'member_id' => $memberId, 'channel' => $channel, 'target' => json_encode($target, \JSON_THROW_ON_ERROR), 'created_at' => $this->now(), 'expires_at' => date('Y-m-d H:i:s', time() + 30 * 86400)];
        Db::table('distribution_shares')->insert($row);
        return $row;
    }

    public function order(int $id, bool $lock = false): ?array
    {
        $q = Db::table('orders')->where('id', $id);
        if ($lock) {
            $q->lockForUpdate();
        }
        $row = $q->first();
        return $row ? (array) $row : null;
    }

    public function orderByNo(string $no): ?array
    {
        $row = Db::table('orders')->where('order_no', $no)->first();
        return $row ? (array) $row : null;
    }

    public function items(int $orderId): array
    {
        return $this->rows(Db::table('order_items')->where('order_id', $orderId)->orderBy('id')->get()->all());
    }

    public function createSnapshot(int $orderId, array $data): void
    {
        Db::table('distribution_order_snapshots')->insertOrIgnore(['order_id' => $orderId, 'buyer_id' => $data['buyer_id'], 'member_id' => $data['member_id'], 'rule_version' => $data['rule_version'], 'snapshot' => json_encode($data, \JSON_THROW_ON_ERROR), 'created_at' => $this->now()]);
    }

    public function snapshot(int $id, bool $lock = false): ?array
    {
        $q = Db::table('distribution_order_snapshots')->where('order_id', $id);
        if ($lock) {
            $q->lockForUpdate();
        }
        $row = $q->first();
        if (! $row) {
            return null;
        }
        $result = (array) $row;
        $result['snapshot'] = json_decode($row->snapshot, true, 512, \JSON_THROW_ON_ERROR);
        return $result;
    }

    public function markPaid(int $orderId): void
    {
        Db::table('distribution_order_snapshots')->where('order_id', $orderId)->update(['paid_at' => $this->now()]);
    }

    public function markCompleted(int $orderId, string $completedAt): void
    {
        Db::table('distribution_order_snapshots')->where('order_id', $orderId)->whereNull('completed_at')->update(['completed_at' => $completedAt]);
    }

    public function claimFirst(int $buyer, int $order): bool
    {
        // The caller locks the buyer before any order rows. Use current reads: an earlier
        // payment can already be committed while its outbox message is still awaiting retry.
        $claimed = Db::table('distribution_first_orders')->where('buyer_id', $buyer)->lockForUpdate()->value('order_id');
        if ($claimed !== null) {
            return (int) $claimed === $order;
        }
        $first = Db::table('distribution_order_snapshots as s')->join('orders as o', 'o.id', '=', 's.order_id')
            ->where('s.buyer_id', $buyer)->whereNotNull('o.pay_time')
            ->orderBy('o.pay_time')->orderBy('o.id')->lockForUpdate()->value('o.id');
        if ($first === null) {
            throw DistributionError::FIRST_ORDER_PAYMENT_MISSING->exception();
        }
        Db::table('distribution_first_orders')->insert(['buyer_id' => $buyer, 'order_id' => (int) $first, 'created_at' => $this->now()]);
        return (int) $first === $order;
    }

    public function createCommission(array $row): int
    {
        return (int) Db::table('distribution_commissions')->insertGetId($row + ['refunded_amount' => 0, 'status' => 'pending', 'created_at' => $this->now(), 'updated_at' => $this->now()]);
    }

    public function commissionsForOrder(int $orderId): array
    {
        return $this->rows(Db::table('distribution_commissions')->where('order_id', $orderId)->orderBy('id')->lockForUpdate()->get()->all());
    }

    public function updateCommission(int $id, array $changes): void
    {
        Db::table('distribution_commissions')->where('id', $id)->update($changes + ['updated_at' => $this->now()]);
    }

    public function refundedGoods(int $orderItemId): int
    {
        return (int) Db::table('payment_refunds as r')->join('order_trade_after_sale as a', static function ($join): void {
            $join->on('a.order_id', '=', 'r.order_id')->whereRaw("a.id = CAST(JSON_UNQUOTE(JSON_EXTRACT(r.extra_data, '$.after_sale_id')) AS UNSIGNED)");
        })->where('a.order_item_id', $orderItemId)->where('r.status', 'success')->sum('r.refund_amount');
    }

    public function totalRefund(int $orderId): int
    {
        return (int) Db::table('payment_refunds')->where('order_id', $orderId)->where('status', 'success')->sum('refund_amount');
    }

    public function hasOpenAfterSale(int $orderId): bool
    {
        return Db::table('order_trade_after_sale')->where('order_id', $orderId)->whereNotIn('status', ['completed', 'closed'])->exists();
    }

    public function pendingOrders(int $limit = 100): array
    {
        return array_map('intval', Db::table('distribution_commissions as c')->join('orders as o', 'o.id', '=', 'c.order_id')->join('distribution_order_snapshots as s', 's.order_id', '=', 'c.order_id')->where('c.status', 'pending')->whereNotNull('s.completed_at')->where('o.status', 'completed')->whereRaw('DATE_ADD(s.completed_at, INTERVAL c.settlement_days DAY) <= NOW()')
            ->whereNotExists(static function ($query): void {
                $query->selectRaw('1')->from('order_trade_after_sale as a')->whereColumn('a.order_id', 'o.id')->whereNotIn('a.status', ['completed', 'closed']);
            })->distinct()->orderBy('c.order_id')->limit($limit)->pluck('c.order_id')->all());
    }

    public function account(int $memberId, bool $lock = false): array
    {
        if ($lock) {
            Db::table('distribution_accounts')->insertOrIgnore(['member_id' => $memberId, 'updated_at' => $this->now()]);
        }
        $q = Db::table('distribution_accounts')->where('member_id', $memberId);
        if ($lock) {
            $q->lockForUpdate();
        }
        $row = $q->first();
        return $row ? (array) $row : ['member_id' => $memberId, 'pending_amount' => 0, 'available_amount' => 0, 'frozen_amount' => 0, 'withdrawn_amount' => 0, 'debt_amount' => 0];
    }

    public function hasHistory(int $memberId): bool
    {
        return Db::table('distribution_accounts')->where('member_id', $memberId)->exists();
    }

    public function ledgerExists(string $key): bool
    {
        return Db::table('distribution_ledger')->where('business_key', $key)->exists();
    }

    public function saveAccount(CommissionAccountEntity $entity, string $key, string $type, int $amount): void
    {
        $state = $entity->state();
        Db::table('distribution_accounts')->where('member_id', $state['member_id'])->update($entity->toArray() + ['updated_at' => $this->now()]);
        Db::table('distribution_ledger')->insert(['business_key' => $key, 'member_id' => $state['member_id'], 'type' => $type, 'amount' => $amount, 'balances' => json_encode($state, \JSON_THROW_ON_ERROR), 'created_at' => $this->now()]);
    }

    public function hasOpenWithdrawal(int $memberId): bool
    {
        return Db::table('distribution_withdrawals')->where('member_id', $memberId)->whereIn('status', ['pending', 'approved'])->lockForUpdate()->first() !== null;
    }

    public function withdrawal(int $id, bool $lock = false): ?array
    {
        $q = Db::table('distribution_withdrawals')->where('id', $id);
        if ($lock) {
            $q->lockForUpdate();
        }
        $row = $q->first();
        return $row ? (array) $row : null;
    }

    public function withdrawalByKey(int $member, string $key): ?array
    {
        $row = Db::table('distribution_withdrawals')->where('member_id', $member)->where('request_key', $key)->lockForUpdate()->first();
        return $row ? (array) $row : null;
    }

    public function saveWithdrawal(?int $id, array $data): int
    {
        if ($id === null) {
            return (int) Db::table('distribution_withdrawals')->insertGetId($data + ['created_at' => $this->now(), 'updated_at' => $this->now()]);
        }
        Db::table('distribution_withdrawals')->where('id', $id)->update($data + ['updated_at' => $this->now()]);
        return $id;
    }

    public function audit(int $operator, string $action, int $subject, array $detail): void
    {
        Db::table('distribution_audit_logs')->insert(['operator_id' => $operator, 'action' => $action, 'subject_id' => $subject, 'detail' => json_encode($detail, \JSON_THROW_ON_ERROR), 'created_at' => $this->now()]);
    }

    public function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    private function rows(array $rows): array
    {
        return array_map(static fn ($r) => (array) $r, $rows);
    }
}
