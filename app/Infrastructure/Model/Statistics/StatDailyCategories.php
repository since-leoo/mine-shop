<?php

declare(strict_types=1);

namespace App\Infrastructure\Model\Statistics;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $date
 * @property int $category_id
 * @property string $category_name
 * @property int $sales_count
 * @property int $sales_amount
 */
class StatDailyCategories extends Model
{
    protected ?string $table = 'stat_daily_categories';

    protected array $fillable = [
        'date', 'category_id', 'category_name', 'sales_count', 'sales_amount',
    ];

    protected array $casts = [
        'category_id' => 'integer', 'sales_count' => 'integer', 'sales_amount' => 'integer',
    ];
}
