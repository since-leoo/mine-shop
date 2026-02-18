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

namespace App\Domain\Infrastructure\Statistics\Service;

use App\Domain\Infrastructure\Statistics\Repository\StatisticsRepository;
use App\Domain\Trade\Review\Repository\ReviewRepository;
use App\Infrastructure\Model\Member\Member;
use App\Infrastructure\Model\Order\Order;
use App\Infrastructure\Model\Product\Product;
use Carbon\Carbon;

/**
 * 统计领域服务.
 *
 * 提供两类能力：
 * 1. 从统计表读取历史聚合数据（定时任务已写入）
 * 2. 实时查询当日/即时数据（不依赖定时任务）
 */
final class DomainStatisticsService
{
    public function __construct(
        private readonly StatisticsRepository $repository,
        private readonly ReviewRepository $reviewRepository,
    ) {}

    // ═══════════════════════════════════════════════════════
    //  定时任务入口
    // ═══════════════════════════════════════════════════════

    /**
     * 聚合指定日期的全部统计数据.
     */
    public function aggregateAll(string $date): void
    {
        $this->repository->aggregateDailySales($date);
        $this->repository->aggregateDailyMembers($date);
        $this->repository->aggregateDailyProducts($date);
        $this->repository->aggregateDailyPayments($date);
        $this->repository->aggregateDailyOrderTypes($date);
        $this->repository->aggregateDailyCategories($date);
        $this->repository->aggregateDailyMemberLevels($date);
        $this->repository->aggregateDailyRegions($date);
    }

    // ═══════════════════════════════════════════════════════
    //  Welcome 首页 — 实时数据 + 简要统计
    // ═══════════════════════════════════════════════════════

    /**
     * 商城首页概览（实时查询，不依赖统计表）.
     */
    public function welcome(): array
    {
        $today = Carbon::today();
        $todayStart = $today->copy()->startOfDay();
        $todayEnd = $today->copy()->endOfDay();

        // 今日实时数据
        $todayOrders = Order::query()->whereBetween('created_at', [$todayStart, $todayEnd])->count();
        $todaySales = (int) Order::query()
            ->where('pay_status', 'paid')
            ->whereBetween('pay_time', [$todayStart, $todayEnd])
            ->sum('pay_amount');
        $todayNewMembers = Member::query()->whereBetween('created_at', [$todayStart, $todayEnd])->count();
        $todayActiveMembers = Member::query()->whereBetween('last_login_at', [$todayStart, $todayEnd])->count();

        // 待处理事项
        $pendingOrders = Order::query()->where('status', 'pending')->count();
        $paidAwaitShip = Order::query()->where('status', 'paid')->count();
        $lowStockProducts = Product::query()
            ->where('status', 'active')
            ->whereHas('skus', static function ($q) {
                $q->where('stock', '<=', 10)->where('stock', '>', 0);
            })
            ->count();
        $outOfStockProducts = Product::query()
            ->where('status', 'active')
            ->whereHas('skus', static function ($q) {
                $q->where('stock', '<=', 0);
            })
            ->count();

        // 总览数据
        $totalMembers = Member::query()->count();
        $totalProducts = Product::query()->where('status', 'active')->count();
        $totalOrders = Order::query()->count();
        $totalSales = (int) Order::query()->where('pay_status', 'paid')->sum('pay_amount');

        // 近7天销售趋势（从统计表读取）
        $weekStart = $today->copy()->subDays(6)->toDateString();
        $weekEnd = $today->copy()->subDay()->toDateString();
        $salesTrend = $this->repository->getSalesTrend($weekStart, $weekEnd);

        // 近7天热销商品 Top5
        $hotProducts = $this->repository->getProductRanking($weekStart, $weekEnd, 5);

        // 评价统计
        $reviewStats = $this->reviewRepository->getStatistics();

        return [
            'today' => [
                'orders' => $todayOrders,
                'sales' => $todaySales,
                'new_members' => $todayNewMembers,
                'active_members' => $todayActiveMembers,
            ],
            'pending' => [
                'pending_payment' => $pendingOrders,
                'pending_shipment' => $paidAwaitShip,
                'low_stock' => $lowStockProducts,
                'out_of_stock' => $outOfStockProducts,
            ],
            'overview' => [
                'total_members' => $totalMembers,
                'total_products' => $totalProducts,
                'total_orders' => $totalOrders,
                'total_sales' => $totalSales,
            ],
            'review' => [
                'today_reviews' => $reviewStats['today_reviews'],
                'pending_reviews' => $reviewStats['pending_reviews'],
                'total_reviews' => $reviewStats['total_reviews'],
                'average_rating' => $reviewStats['average_rating'],
            ],
            'sales_trend' => $salesTrend,
            'hot_products' => $hotProducts,
        ];
    }

    // ═══════════════════════════════════════════════════════
    //  Analysis 分析页 — 商城核心数据分析
    // ═══════════════════════════════════════════════════════

    /**
     * 商城数据分析（从统计表读取 + 部分实时）.
     */
    public function analysis(string $startDate, string $endDate): array
    {
        // 核心指标汇总
        $salesSummary = $this->repository->getSalesSummary($startDate, $endDate);
        $membersSummary = $this->repository->getMembersSummary($startDate, $endDate);

        // 实时访问人数（用活跃会员近似）
        $today = Carbon::today();
        $todayVisitors = Member::query()
            ->whereBetween('last_login_at', [$today->startOfDay(), $today->copy()->endOfDay()])
            ->count();

        // 趋势数据
        $salesTrend = $this->repository->getSalesTrend($startDate, $endDate);
        $membersTrend = $this->repository->getMembersTrend($startDate, $endDate);

        // 分布数据
        $paymentBreakdown = $this->repository->getPaymentBreakdown($startDate, $endDate);
        $orderTypeBreakdown = $this->repository->getOrderTypeBreakdown($startDate, $endDate);

        // 排行榜
        $productRanking = $this->repository->getProductRanking($startDate, $endDate, 10);
        $categoryRanking = $this->repository->getCategoryRanking($startDate, $endDate, 10);

        // 转化率
        $conversionRate = $salesSummary['order_count'] > 0
            ? round($salesSummary['paid_order_count'] / $salesSummary['order_count'] * 100, 2)
            : 0;

        // 同比数据（上一个同等周期）
        $daysDiff = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        $prevEnd = Carbon::parse($startDate)->subDay()->toDateString();
        $prevStart = Carbon::parse($prevEnd)->subDays($daysDiff - 1)->toDateString();
        $prevSalesSummary = $this->repository->getSalesSummary($prevStart, $prevEnd);
        $prevMembersSummary = $this->repository->getMembersSummary($prevStart, $prevEnd);

        return [
            'summary' => [
                'total_sales' => $salesSummary['paid_amount'],
                'total_orders' => $salesSummary['order_count'],
                'paid_orders' => $salesSummary['paid_order_count'],
                'total_visitors' => $todayVisitors,
                'new_members' => $membersSummary['new_members'],
                'total_members' => $membersSummary['total_members'],
                'paying_members' => $membersSummary['paying_members'],
                'avg_order_amount' => $salesSummary['avg_order_amount'],
                'refund_amount' => $salesSummary['refund_amount'],
                'refund_count' => $salesSummary['refund_count'],
                'conversion_rate' => $conversionRate,
                'shipping_fee_total' => $salesSummary['shipping_fee_total'],
                'discount_total' => $salesSummary['discount_total'],
            ],
            'comparison' => [
                'prev_sales' => $prevSalesSummary['paid_amount'],
                'prev_orders' => $prevSalesSummary['order_count'],
                'prev_new_members' => $prevMembersSummary['new_members'],
                'sales_growth' => $this->calcGrowthRate($salesSummary['paid_amount'], $prevSalesSummary['paid_amount']),
                'orders_growth' => $this->calcGrowthRate($salesSummary['order_count'], $prevSalesSummary['order_count']),
                'members_growth' => $this->calcGrowthRate($membersSummary['new_members'], $prevMembersSummary['new_members']),
            ],
            'trends' => [
                'sales' => $salesTrend,
                'members' => $membersTrend,
            ],
            'breakdown' => [
                'payment_methods' => $paymentBreakdown,
                'order_types' => $orderTypeBreakdown,
            ],
            'ranking' => [
                'products' => $productRanking,
                'categories' => $categoryRanking,
            ],
        ];
    }

    // ═══════════════════════════════════════════════════════
    //  Report 多维度报表
    // ═══════════════════════════════════════════════════════

    /**
     * 多维度统计报表.
     */
    public function report(string $startDate, string $endDate): array
    {
        return [
            // 销售趋势（按日）
            'sales_trend' => $this->repository->getSalesTrend($startDate, $endDate),
            // 销售汇总
            'sales_summary' => $this->repository->getSalesSummary($startDate, $endDate),
            // 会员趋势
            'members_trend' => $this->repository->getMembersTrend($startDate, $endDate),
            // 会员汇总
            'members_summary' => $this->repository->getMembersSummary($startDate, $endDate),
            // 商品销售排行 Top20
            'product_ranking' => $this->repository->getProductRanking($startDate, $endDate, 20),
            // 分类销售排行
            'category_ranking' => $this->repository->getCategoryRanking($startDate, $endDate, 20),
            // 支付方式分布
            'payment_breakdown' => $this->repository->getPaymentBreakdown($startDate, $endDate),
            // 支付方式趋势
            'payment_trend' => $this->repository->getPaymentTrend($startDate, $endDate),
            // 订单类型分布
            'order_type_breakdown' => $this->repository->getOrderTypeBreakdown($startDate, $endDate),
            // 订单类型趋势
            'order_type_trend' => $this->repository->getOrderTypeTrend($startDate, $endDate),
            // 会员等级分布
            'member_level_breakdown' => $this->repository->getMemberLevelBreakdown($endDate),
            // 地区销售排行
            'region_ranking' => $this->repository->getRegionRanking($startDate, $endDate, 20),
            // 退款分析
            'refund_analysis' => $this->buildRefundAnalysis($startDate, $endDate),
            // 客单价分布
            'order_amount_distribution' => $this->buildOrderAmountDistribution($startDate, $endDate),
        ];
    }

    /**
     * 退款分析.
     */
    private function buildRefundAnalysis(string $startDate, string $endDate): array
    {
        $salesSummary = $this->repository->getSalesSummary($startDate, $endDate);
        $refundRate = $salesSummary['paid_order_count'] > 0
            ? round($salesSummary['refund_count'] / $salesSummary['paid_order_count'] * 100, 2)
            : 0;

        return [
            'refund_count' => $salesSummary['refund_count'],
            'refund_amount' => $salesSummary['refund_amount'],
            'refund_rate' => $refundRate,
        ];
    }

    /**
     * 客单价分布（实时查询）.
     */
    private function buildOrderAmountDistribution(string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $ranges = [
            ['label' => '0-50元', 'min' => 0, 'max' => 5000],
            ['label' => '50-100元', 'min' => 5000, 'max' => 10000],
            ['label' => '100-200元', 'min' => 10000, 'max' => 20000],
            ['label' => '200-500元', 'min' => 20000, 'max' => 50000],
            ['label' => '500-1000元', 'min' => 50000, 'max' => 100000],
            ['label' => '1000元以上', 'min' => 100000, 'max' => \PHP_INT_MAX],
        ];

        $result = [];
        foreach ($ranges as $range) {
            $count = Order::query()
                ->where('pay_status', 'paid')
                ->whereBetween('pay_time', [$start, $end])
                ->where('pay_amount', '>=', $range['min'])
                ->where('pay_amount', '<', $range['max'])
                ->count();

            $result[] = [
                'label' => $range['label'],
                'count' => $count,
            ];
        }

        return $result;
    }

    /**
     * 计算增长率.
     */
    private function calcGrowthRate(int $current, int $previous): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        return round(($current - $previous) / $previous * 100, 2);
    }
}
