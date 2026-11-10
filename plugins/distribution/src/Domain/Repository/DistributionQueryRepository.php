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

use Hyperf\DbConnection\Db;

class DistributionQueryRepository
{
    public function overview(?int $memberId, array $filters = []): array
    {
        $query = $this->ordersQuery();
        $this->scope($query, $memberId, $filters, 'o.member_id');
        $this->dates($query, $filters, 'o.pay_time');
        $stats = (array) $query->selectRaw('COUNT(*) as paid_order_count,COALESCE(SUM(o.pay_amount),0) as paid_amount,COALESCE(SUM(LEAST(o.pay_amount,COALESCE(r.amount,0))),0) as refund_amount,COALESCE(SUM(GREATEST(0,o.pay_amount-COALESCE(r.amount,0))),0) as net_amount,COALESCE(SUM(o.pay_amount>COALESCE(r.amount,0)),0) as valid_order_count,COUNT(DISTINCT CASE WHEN o.pay_amount>COALESCE(r.amount,0) THEN o.member_id END) as consumer_count')->first();
        if ($memberId !== null) {
            $stats['direct_count'] = Db::table('members')->where('referrer_id', $memberId)->count();
            $stats['team_count'] = Db::table('distribution_paths')->where('ancestor_id', $memberId)->count();
            $account = (array) (Db::table('distribution_accounts')->where('member_id', $memberId)->first() ?? []);
        } else {
            $stats['member_count'] = Db::table('members')->count();
            $stats['invited_member_count'] = Db::table('members')->whereNotNull('referrer_id')->count();
            $stats['platform_member_count'] = Db::table('members')->whereNull('referrer_id')->count();
            $stats['over_capacity_count'] = Db::table('members')->whereIn('id', Db::table('members')->select('referrer_id')->whereNotNull('referrer_id')->groupBy('referrer_id')->havingRaw('COUNT(*) > 3'))->count();
            $account = (array) Db::table('distribution_accounts')->selectRaw('SUM(pending_amount) as pending_amount,SUM(available_amount) as available_amount,SUM(frozen_amount) as frozen_amount,SUM(withdrawn_amount) as withdrawn_amount,SUM(debt_amount) as debt_amount')->first();
        }
        foreach (['pending_amount' => 'pending_commission', 'available_amount' => 'available_commission', 'frozen_amount' => 'frozen_commission', 'withdrawn_amount' => 'withdrawn_commission', 'debt_amount' => 'debt_amount'] as $field => $key) {
            $stats[$key] = (int) ($account[$field] ?? 0);
        }
        $commissions = Db::table('distribution_commissions')->where('status', 'settled');
        $withdrawals = Db::table('distribution_withdrawals')->where('status', 'paid');
        if ($memberId !== null) {
            $commissions->where('member_id', $memberId);
            $withdrawals->where('member_id', $memberId);
        }
        $stats['settled_commission_amount'] = (int) $commissions->sum(Db::raw('amount-refunded_amount'));
        $stats['paid_withdrawal_amount'] = (int) $withdrawals->sum('net_amount');
        return array_map('intval', $stats);
    }

    public function members(?int $viewer, array $filters): array
    {
        $query = Db::table('members as m')->select(['m.id', 'm.nickname', 'm.avatar', 'm.invite_code', 'm.referrer_id', 'm.created_at', 'm.status']);
        $root = (int) ($filters['member_id'] ?? $viewer ?? 0);
        if ($viewer !== null && ($viewer <= 0 || $root <= 0)) {
            throw new \DomainException('无权查看该团队');
        }
        if ($root > 0) {
            if ($viewer !== null && $root !== $viewer && ! Db::table('distribution_paths')->where('ancestor_id', $viewer)->where('descendant_id', $root)->exists()) {
                throw new \DomainException('无权查看该团队');
            }
            $query->join('distribution_paths as p', 'p.descendant_id', '=', 'm.id')->where('p.ancestor_id', $root)->addSelect('p.depth');
            if (($filters['scope'] ?? 'all') === 'direct') {
                $query->where('p.depth', 1);
            }
        }
        if (! empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(static function ($q) use ($keyword): void {
                $q->where('m.nickname', 'like', '%' . $keyword . '%')->orWhere('m.invite_code', $keyword);
                if (ctype_digit($keyword)) {
                    $q->orWhere('m.id', (int) $keyword);
                }
            });
        }
        $page = $this->page($query->orderByDesc('m.id'), $filters);
        foreach ($page['list'] as &$row) {
            $stats = $this->ordersQuery()->where('o.member_id', $row['id']);
            $this->dates($stats, $filters, 'o.pay_time');
            $row = array_merge($row, (array) $stats->selectRaw('COUNT(*) as paid_order_count,COALESCE(SUM(o.pay_amount>COALESCE(r.amount,0)),0) as valid_order_count,COALESCE(SUM(GREATEST(0,o.pay_amount-COALESCE(r.amount,0))),0) as net_amount')->first());
            $row['direct_count'] = Db::table('members')->where('referrer_id', $row['id'])->count();
            $row['team_count'] = Db::table('distribution_paths')->where('ancestor_id', $row['id'])->count();
        }
        return $page;
    }

    public function orders(?int $viewer, array $filters): array
    {
        $query = $this->ordersQuery()->join('members as m', 'm.id', '=', 'o.member_id');
        $this->scope($query, $viewer, $filters, 'o.member_id');
        $this->dates($query, $filters, 'o.pay_time');
        if (! empty($filters['status'])) {
            $query->where('o.status', $filters['status']);
        }
        if (! empty($filters['keyword'])) {
            $query->where('o.order_no', 'like', '%' . $filters['keyword'] . '%');
        }
        $query->select(['o.id', 'o.order_no', 'o.member_id', 'm.nickname', 'o.status', 'o.pay_time as paid_at', 'o.pay_amount as paid_amount'])->selectRaw('LEAST(o.pay_amount,COALESCE(r.amount,0)) as refund_amount,GREATEST(0,o.pay_amount-COALESCE(r.amount,0)) as net_amount');
        $page = $this->page($query->orderByDesc('o.id'), $filters);
        foreach ($page['list'] as &$row) {
            $row['product_summary'] = Db::table('order_items')->where('order_id', $row['id'])->orderBy('id')->limit(3)->pluck('product_name')->implode('、');
            $commissions = Db::table('distribution_commissions')->where('order_id', $row['id']);
            if ($viewer !== null) {
                $commissions->where('member_id', $viewer);
            }
            $row['commission_amount'] = (int) $commissions->sum(Db::raw('amount-refunded_amount'));
        }
        return $page;
    }

    public function commissions(?int $member, array $filters): array
    {
        $query = Db::table('distribution_commissions as c')->join('orders as o', 'o.id', '=', 'c.order_id')->select('c.*', 'o.order_no');
        if ($member !== null) {
            $query->where('c.member_id', $member);
        } elseif (! empty($filters['member_id'])) {
            $query->where('c.member_id', (int) $filters['member_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('c.status', $filters['status']);
        }
        $this->dates($query, $filters, 'c.created_at');
        return $this->page($query->orderByDesc('c.id'), $filters);
    }

    public function withdrawals(?int $member, array $filters): array
    {
        $query = Db::table('distribution_withdrawals');
        if ($member !== null) {
            $query->where('member_id', $member);
        } elseif (! empty($filters['member_id'])) {
            $query->where('member_id', (int) $filters['member_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        $this->dates($query, $filters, 'created_at');
        return $this->page($query->orderByDesc('id'), $filters);
    }

    public function logs(array $filters): array
    {
        return $this->page(Db::table('distribution_audit_logs')->orderByDesc('id'), $filters);
    }

    public function ledger(int $member, array $filters): array
    {
        return $this->page(Db::table('distribution_ledger')->where('member_id', $member)->orderByDesc('id'), $filters);
    }

    private function refunds()
    {
        return Db::table('payment_refunds')->selectRaw('order_id,SUM(refund_amount) as amount')->where('status', 'success')->groupBy('order_id');
    }

    private function ordersQuery()
    {
        return Db::table('orders as o')->leftJoinSub($this->refunds(), 'r', 'r.order_id', '=', 'o.id')->whereNotNull('o.pay_time');
    }

    private function dates($query, array $filters, string $column): void
    {
        if (! empty($filters['start_date'])) {
            $query->where($column, '>=', $filters['start_date'] . ' 00:00:00');
        }
        if (! empty($filters['end_date'])) {
            $query->where($column, '<', date('Y-m-d', strtotime($filters['end_date'] . ' +1 day')) . ' 00:00:00');
        }
    }

    private function scope($query, ?int $viewer, array $filters, string $column): void
    {
        $root = (int) ($filters['member_id'] ?? $viewer ?? 0);
        if ($viewer !== null && ($viewer <= 0 || $root <= 0)) {
            throw new \DomainException('无权查看该团队');
        }
        if ($viewer !== null && $root !== $viewer && ! Db::table('distribution_paths')->where('ancestor_id', $viewer)->where('descendant_id', $root)->exists()) {
            throw new \DomainException('无权查看该成员的数据');
        }
        if ($root > 0) {
            if (($filters['scope'] ?? 'all') === 'self') {
                if ($viewer !== null && $root === $viewer) {
                    throw new \DomainException('团队数据不包含本人');
                }
                $query->where($column, $root);
            } else {
                $ids = Db::table('distribution_paths')->select('descendant_id')->where('ancestor_id', $root);
                if (($filters['scope'] ?? 'all') === 'direct') {
                    $ids->where('depth', 1);
                }
                $query->whereIn($column, $ids);
            }
        } else {
            $query->whereIn($column, Db::table('members')->select('id')->whereNotNull('referrer_id'));
        }
    }

    private function page($query, array $filters): array
    {
        $size = max(1, min(100, (int) ($filters['page_size'] ?? 20)));
        $page = max(1, (int) ($filters['page'] ?? 1));
        return ['list' => array_map(static fn ($r) => (array) $r, (clone $query)->offset(($page - 1) * $size)->limit($size)->get()->all()), 'total' => (clone $query)->count(), 'page' => $page, 'page_size' => $size];
    }
}
