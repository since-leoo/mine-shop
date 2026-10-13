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

namespace App\Infrastructure\Plugin;

use App\Domain\Infrastructure\Plugin\Contract\AnalyticsQueryInterface;
use App\Domain\Infrastructure\Plugin\Contract\CommerceOverviewInterface;
use App\Domain\Infrastructure\Plugin\Contract\ReviewStatisticsInterface;

final class NullAnalyticsQuery implements AnalyticsQueryInterface
{
    public function __construct(private readonly CommerceOverviewInterface $commerce, private readonly ReviewStatisticsInterface $reviews) {}

    public function welcome(): array
    {
        return $this->commerce->overview() + ['review' => $this->reviews->getStatistics(), 'sales_trend' => [], 'hot_products' => [], 'analytics_enabled' => false];
    }

    public function analysis(string $startDate, string $endDate): array
    {
        return ['enabled' => false, 'summary' => array_fill_keys(['total_sales', 'total_orders', 'paid_orders', 'total_visitors', 'new_members', 'total_members', 'paying_members', 'avg_order_amount', 'refund_amount', 'refund_count', 'conversion_rate', 'shipping_fee_total', 'discount_total'], 0), 'comparison' => array_fill_keys(['prev_sales', 'prev_orders', 'prev_new_members', 'sales_growth', 'orders_growth', 'members_growth'], 0), 'trends' => ['sales' => [], 'members' => []], 'breakdown' => ['payment_methods' => [], 'order_types' => []], 'ranking' => ['products' => [], 'categories' => []]];
    }

    public function report(string $startDate, string $endDate): array
    {
        return array_fill_keys(['sales_trend', 'members_trend', 'product_ranking', 'category_ranking', 'payment_breakdown', 'payment_trend', 'order_type_breakdown', 'order_type_trend', 'member_level_breakdown', 'region_ranking', 'order_amount_distribution'], []) + ['enabled' => false, 'sales_summary' => array_fill_keys(['order_count', 'paid_order_count', 'order_amount', 'paid_amount', 'refund_amount', 'refund_count', 'shipping_fee_total', 'discount_total', 'avg_order_amount'], 0), 'members_summary' => array_fill_keys(['new_members', 'active_members', 'total_members', 'paying_members'], 0), 'refund_analysis' => ['refund_count' => 0, 'refund_amount' => 0, 'refund_rate' => 0]];
    }
}
