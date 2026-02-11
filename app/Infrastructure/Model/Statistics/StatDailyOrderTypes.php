<?php

declare(strict_types=1);

namespace App\Infrastructure\Model\Statistics;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $date
 * @property string $order_type
 * @property int $order_count
 * @property int $order_amount
 * @property int $paid_count
 * @property int $paid_amount
 */
class StatDailyOrderTypes extends Model
{
    protected ?string $table = 'stat_daily_order_types';

    protected array $fillable = [
        'date', 'order_type', 'order_count', 'order_amount', 'paid_count', 'paid_amount',
    ];

    protected array $casts = [
        'order_count' => 'integer', 'order_amount' => 'integer',
        'paid_count' => 'integer', 'paid_amount' => 'integer',
    ];
}
