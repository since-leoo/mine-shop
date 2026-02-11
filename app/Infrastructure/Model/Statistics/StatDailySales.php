<?php

declare(strict_types=1);

namespace App\Infrastructure\Model\Statistics;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $date
 * @property int $order_count
 * @property int $paid_order_count
 * @property int $order_amount
 * @property int $paid_amount
 * @property int $refund_amount
 * @property int $refund_count
 * @property int $shipping_fee_total
 * @property int $discount_total
 * @property int $coupon_total
 * @property int $avg_order_amount
 */
class StatDailySales extends Model
{
    protected ?string $table = 'stat_daily_sales';

    protected array $fillable = [
        'date', 'order_count', 'paid_order_count', 'order_amount', 'paid_amount',
        'refund_amount', 'refund_count', 'shipping_fee_total', 'discount_total',
        'coupon_total', 'avg_order_amount',
    ];

    protected array $casts = [
        'order_count' => 'integer', 'paid_order_count' => 'integer',
        'order_amount' => 'integer', 'paid_amount' => 'integer',
        'refund_amount' => 'integer', 'refund_count' => 'integer',
        'shipping_fee_total' => 'integer', 'discount_total' => 'integer',
        'coupon_total' => 'integer', 'avg_order_amount' => 'integer',
    ];
}
