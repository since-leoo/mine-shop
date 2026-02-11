<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Statistics\Repository;

use App\Infrastructure\Model\Member\Member;
use App\Infrastructure\Model\Order\Order;
use App\Infrastructure\Model\Order\OrderItem;
use App\Infrastructure\Model\Order\OrderPayment;
use App\Infrastructure\Model\Statistics\StatDailyCategories;
use App\Infrastructure\Model\Statistics\StatDailyMemberLevels;
use App\Infrastructure\Model\Statistics\StatDailyMembers;
use App\Infrastructure\Model\Statistics\StatDailyOrderTypes;
use App\Infrastructure\Model\Statistics\StatDailyPayments;
use App\Infrastructure\Model\Statistics\StatDailyProducts;
use App\Infrastructure\Model\Statistics\StatDailyRegions;
use App\Infrastructure\Model\Statistics\StatDailySales;
use Carbon\Carbon;
use Hyperf\DbConnection\Db;

/**
 * 统计数据仓储 — 负责聚合写入和读取统计表.
 */
final class StatisticsRepository
{
    /**
     * 获取数据库表前缀.
     */
    private function prefix(): string
    {
        return (string) config('databases.default.prefix', '');
    }

    // ═══════════════════════════════════════════════════════
    //  聚合写入（定时任务调用）
    // ═══════════════════════════════════════════════════════

    /**
     * 聚合指定日期的销售数据并写入 stat_daily_sales.
     */
    public function aggregateDailySales(string $date): void
    {
        $start = Carbon::parse($date)->startOfDay();
        $end = Carbon::parse($date)->endOfDay();

        $row = Order::query()
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw("SUM(CASE WHEN pay_status = 'paid' THEN 1 ELSE 0 END) as paid_order_count")
            ->selectRaw('COALESCE(SUM(total_amount), 0) as order_amount')
            ->selectRaw("COALESCE(SUM(CASE WHEN pay_status = 'paid' THEN pay_amount ELSE 0 END), 0) as paid_amount")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'refunded' THEN pay_amount ELSE 0 END), 0) as refund_amount")
            ->selectRaw("SUM(CASE WHEN status = 'refunded' THEN 1 ELSE 0 END) as refund_count")
            ->selectRaw('COALESCE(SUM(shipping_fee), 0) as shipping_fee_total')
            ->selectRaw('COALESCE(SUM(discount_amount), 0) as discount_total')
            ->whereBetween('created_at', [$start, $end])
            ->first();

        // 优惠券总额从 order_items 或 orders 的 coupon_amount 字段（如果有）
        // 当前 orders 表没有 coupon_amount 字段，从 discount_amount 近似
        $couponTotal = 0;

        $paidCount = (int) ($row->paid_order_count ?? 0);
        $paidAmount = (int) ($row->paid_amount ?? 0);
        $avgOrderAmount = $paidCount > 0 ? (int) round($paidAmount / $paidCount) : 0;

        StatDailySales::query()->updateOrCreate(
            ['date' => $date],
            [
                'order_count' => (int) ($row->order_count ?? 0),
                'paid_order_count' => $paidCount,
                'order_amount' => (int) ($row->order_amount ?? 0),
                'paid_amount' => $paidAmount,
                'refund_amount' => (int) ($row->refund_amount ?? 0),
                'refund_count' => (int) ($row->refund_count ?? 0),
                'shipping_fee_total' => (int) ($row->shipping_fee_total ?? 0),
                'discount_total' => (int) ($row->discount_total ?? 0),
                'coupon_total' => $couponTotal,
                'avg_order_amount' => $avgOrderAmount,
            ]
        );
    }

    /**
     * 聚合指定日期的会员数据.
     */
    public function aggregateDailyMembers(string $date): void
    {
        $start = Carbon::parse($date)->startOfDay();
        $end = Carbon::parse($date)->endOfDay();

        $new_members = Member::query()->whereBetween('created_at', [$start, $end])->count();
        $active_members = Member::query()->whereBetween('last_login_at', [$start, $end])->count();
        $total_members = Member::query()->where('created_at', '<=', $end)->count();
        $paying_members = Order::query()
            ->where('pay_status', 'paid')
            ->whereBetween('pay_time', [$start, $end])
            ->distinct('member_id')
            ->count('member_id');

        StatDailyMembers::query()->updateOrCreate(
            ['date' => $date],
            compact('new_members', 'active_members', 'total_members', 'paying_members')
        );
    }

    /**
     * 聚合指定日期的商品销售数据.
     */
    public function aggregateDailyProducts(string $date): void
    {
        $start = Carbon::parse($date)->startOfDay();
        $end = Carbon::parse($date)->endOfDay();

        $p = $this->prefix();
        $rows = Db::select("
            SELECT oi.product_id, oi.product_name,
                   SUM(oi.quantity) as sales_count,
                   SUM(oi.total_price) as sales_amount
            FROM {$p}order_items oi
            JOIN {$p}orders o ON o.id = oi.order_id
            WHERE o.pay_status = 'paid'
              AND o.pay_time BETWEEN ? AND ?
            GROUP BY oi.product_id, oi.product_name
        ", [$start, $end]);

        foreach ($rows as $row) {
            StatDailyProducts::query()->updateOrCreate(
                ['date' => $date, 'product_id' => $row->product_id],
                [
                    'product_name' => $row->product_name,
                    'sales_count' => (int) $row->sales_count,
                    'sales_amount' => (int) $row->sales_amount,
                    'view_count' => 0,
                ]
            );
        }
    }

    /**
     * 聚合指定日期的支付方式数据.
     */
    public function aggregateDailyPayments(string $date): void
    {
        $start = Carbon::parse($date)->startOfDay();
        $end = Carbon::parse($date)->endOfDay();

        $rows = OrderPayment::query()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw('payment_method, COUNT(*) as pay_count, COALESCE(SUM(paid_amount), 0) as pay_amount')
            ->groupBy('payment_method')
            ->get();

        foreach ($rows as $row) {
            $method = $row->payment_method ?: 'unknown';
            // 退款统计
            $refund = OrderPayment::query()
                ->where('payment_method', $method)
                ->where('status', 'refunded')
                ->whereBetween('updated_at', [$start, $end])
                ->selectRaw('COUNT(*) as refund_count, COALESCE(SUM(refund_amount), 0) as refund_amount')
                ->first();

            StatDailyPayments::query()->updateOrCreate(
                ['date' => $date, 'payment_method' => $method],
                [
                    'pay_count' => (int) $row->pay_count,
                    'pay_amount' => (int) $row->pay_amount,
                    'refund_count' => (int) ($refund->refund_count ?? 0),
                    'refund_amount' => (int) ($refund->refund_amount ?? 0),
                ]
            );
        }
    }

    /**
     * 聚合指定日期的订单类型数据.
     */
    public function aggregateDailyOrderTypes(string $date): void
    {
        $start = Carbon::parse($date)->startOfDay();
        $end = Carbon::parse($date)->endOfDay();

        $rows = Order::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('order_type, COUNT(*) as order_count, COALESCE(SUM(total_amount), 0) as order_amount')
            ->selectRaw("SUM(CASE WHEN pay_status = 'paid' THEN 1 ELSE 0 END) as paid_count")
            ->selectRaw("COALESCE(SUM(CASE WHEN pay_status = 'paid' THEN pay_amount ELSE 0 END), 0) as paid_amount")
            ->groupBy('order_type')
            ->get();

        foreach ($rows as $row) {
            StatDailyOrderTypes::query()->updateOrCreate(
                ['date' => $date, 'order_type' => $row->order_type],
                [
                    'order_count' => (int) $row->order_count,
                    'order_amount' => (int) $row->order_amount,
                    'paid_count' => (int) $row->paid_count,
                    'paid_amount' => (int) $row->paid_amount,
                ]
            );
        }
    }

    /**
     * 聚合指定日期的分类销售数据.
     */
    public function aggregateDailyCategories(string $date): void
    {
        $start = Carbon::parse($date)->startOfDay();
        $end = Carbon::parse($date)->endOfDay();

        $p = $this->prefix();
        $rows = Db::select("
            SELECT p.category_id, c.name as category_name,
                   SUM(oi.quantity) as sales_count,
                   SUM(oi.total_price) as sales_amount
            FROM {$p}order_items oi
            JOIN {$p}orders o ON o.id = oi.order_id
            JOIN {$p}products p ON p.id = oi.product_id
            LEFT JOIN {$p}categories c ON c.id = p.category_id
            WHERE o.pay_status = 'paid'
              AND o.pay_time BETWEEN ? AND ?
            GROUP BY p.category_id, c.name
        ", [$start, $end]);

        foreach ($rows as $row) {
            StatDailyCategories::query()->updateOrCreate(
                ['date' => $date, 'category_id' => $row->category_id],
                [
                    'category_name' => $row->category_name ?? '未分类',
                    'sales_count' => (int) $row->sales_count,
                    'sales_amount' => (int) $row->sales_amount,
                ]
            );
        }
    }

    /**
     * 聚合指定日期的会员等级分布.
     */
    public function aggregateDailyMemberLevels(string $date): void
    {
        $end = Carbon::parse($date)->endOfDay();

        $rows = Member::query()
            ->where('created_at', '<=', $end)
            ->selectRaw("COALESCE(level, 'bronze') as level, COUNT(*) as member_count")
            ->groupBy('level')
            ->get();

        foreach ($rows as $row) {
            StatDailyMemberLevels::query()->updateOrCreate(
                ['date' => $date, 'level' => $row->level],
                ['member_count' => (int) $row->member_count]
            );
        }
    }

    /**
     * 聚合指定日期的地区销售数据.
     */
    public function aggregateDailyRegions(string $date): void
    {
        $start = Carbon::parse($date)->startOfDay();
        $end = Carbon::parse($date)->endOfDay();

        $p = $this->prefix();
        $rows = Db::select("
            SELECT COALESCE(NULLIF(oa.province, ''), '未知') as province,
                   COUNT(DISTINCT o.id) as order_count,
                   COALESCE(SUM(o.pay_amount), 0) as order_amount
            FROM {$p}orders o
            LEFT JOIN {$p}order_addresses oa ON oa.order_id = o.id
            WHERE o.pay_status = 'paid'
              AND o.pay_time BETWEEN ? AND ?
            GROUP BY province
            ORDER BY order_amount DESC
        ", [$start, $end]);

        foreach ($rows as $row) {
            StatDailyRegions::query()->updateOrCreate(
                ['date' => $date, 'province' => $row->province],
                [
                    'order_count' => (int) $row->order_count,
                    'order_amount' => (int) $row->order_amount,
                ]
            );
        }
    }

    // ═══════════════════════════════════════════════════════
    //  读取查询（Dashboard 接口调用）
    // ═══════════════════════════════════════════════════════

    /**
     * 获取日期范围内的每日销售趋势.
     */
    public function getSalesTrend(string $startDate, string $endDate): array
    {
        return StatDailySales::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * 获取日期范围内的销售汇总.
     */
    public function getSalesSummary(string $startDate, string $endDate): array
    {
        $row = StatDailySales::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('SUM(order_count) as order_count, SUM(paid_order_count) as paid_order_count')
            ->selectRaw('SUM(order_amount) as order_amount, SUM(paid_amount) as paid_amount')
            ->selectRaw('SUM(refund_amount) as refund_amount, SUM(refund_count) as refund_count')
            ->selectRaw('SUM(shipping_fee_total) as shipping_fee_total, SUM(discount_total) as discount_total')
            ->first();

        $paidCount = (int) ($row->paid_order_count ?? 0);
        $paidAmount = (int) ($row->paid_amount ?? 0);

        return [
            'order_count' => (int) ($row->order_count ?? 0),
            'paid_order_count' => $paidCount,
            'order_amount' => (int) ($row->order_amount ?? 0),
            'paid_amount' => $paidAmount,
            'refund_amount' => (int) ($row->refund_amount ?? 0),
            'refund_count' => (int) ($row->refund_count ?? 0),
            'shipping_fee_total' => (int) ($row->shipping_fee_total ?? 0),
            'discount_total' => (int) ($row->discount_total ?? 0),
            'avg_order_amount' => $paidCount > 0 ? (int) round($paidAmount / $paidCount) : 0,
        ];
    }

    /**
     * 获取日期范围内的会员趋势.
     */
    public function getMembersTrend(string $startDate, string $endDate): array
    {
        return StatDailyMembers::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * 获取日期范围内的会员汇总.
     */
    public function getMembersSummary(string $startDate, string $endDate): array
    {
        $row = StatDailyMembers::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('SUM(new_members) as new_members, SUM(paying_members) as paying_members')
            ->first();

        $latest = StatDailyMembers::query()
            ->where('date', '<=', $endDate)
            ->orderByDesc('date')
            ->first();

        return [
            'new_members' => (int) ($row->new_members ?? 0),
            'paying_members' => (int) ($row->paying_members ?? 0),
            'total_members' => (int) ($latest->total_members ?? 0),
            'active_members' => (int) ($latest->active_members ?? 0),
        ];
    }

    /**
     * 获取商品销售排行.
     */
    public function getProductRanking(string $startDate, string $endDate, int $limit = 10): array
    {
        return StatDailyProducts::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('product_id, product_name, SUM(sales_count) as sales_count, SUM(sales_amount) as sales_amount')
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('sales_amount')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * 获取支付方式分布.
     */
    public function getPaymentBreakdown(string $startDate, string $endDate): array
    {
        return StatDailyPayments::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('payment_method, SUM(pay_count) as pay_count, SUM(pay_amount) as pay_amount')
            ->selectRaw('SUM(refund_count) as refund_count, SUM(refund_amount) as refund_amount')
            ->groupBy('payment_method')
            ->orderByDesc('pay_amount')
            ->get()
            ->toArray();
    }

    /**
     * 获取订单类型分布.
     */
    public function getOrderTypeBreakdown(string $startDate, string $endDate): array
    {
        return StatDailyOrderTypes::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('order_type, SUM(order_count) as order_count, SUM(order_amount) as order_amount')
            ->selectRaw('SUM(paid_count) as paid_count, SUM(paid_amount) as paid_amount')
            ->groupBy('order_type')
            ->orderByDesc('order_amount')
            ->get()
            ->toArray();
    }

    /**
     * 获取分类销售排行.
     */
    public function getCategoryRanking(string $startDate, string $endDate, int $limit = 10): array
    {
        return StatDailyCategories::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('category_id, category_name, SUM(sales_count) as sales_count, SUM(sales_amount) as sales_amount')
            ->groupBy('category_id', 'category_name')
            ->orderByDesc('sales_amount')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * 获取会员等级分布（取最近一天的快照）.
     */
    public function getMemberLevelBreakdown(string $endDate): array
    {
        $latestDate = StatDailyMemberLevels::query()
            ->where('date', '<=', $endDate)
            ->max('date');

        if (! $latestDate) {
            return [];
        }

        return StatDailyMemberLevels::query()
            ->where('date', $latestDate)
            ->get()
            ->toArray();
    }

    /**
     * 获取地区销售排行.
     */
    public function getRegionRanking(string $startDate, string $endDate, int $limit = 10): array
    {
        return StatDailyRegions::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('province, SUM(order_count) as order_count, SUM(order_amount) as order_amount')
            ->groupBy('province')
            ->orderByDesc('order_amount')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * 获取支付方式趋势.
     */
    public function getPaymentTrend(string $startDate, string $endDate): array
    {
        return StatDailyPayments::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * 获取订单类型趋势.
     */
    public function getOrderTypeTrend(string $startDate, string $endDate): array
    {
        return StatDailyOrderTypes::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get()
            ->toArray();
    }
}
