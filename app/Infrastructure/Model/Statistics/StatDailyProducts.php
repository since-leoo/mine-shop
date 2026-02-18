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

namespace App\Infrastructure\Model\Statistics;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $date
 * @property int $product_id
 * @property string $product_name
 * @property int $sales_count
 * @property int $sales_amount
 * @property int $view_count
 */
class StatDailyProducts extends Model
{
    protected ?string $table = 'stat_daily_products';

    protected array $fillable = [
        'date', 'product_id', 'product_name', 'sales_count', 'sales_amount', 'view_count',
    ];

    protected array $casts = [
        'product_id' => 'integer', 'sales_count' => 'integer',
        'sales_amount' => 'integer', 'view_count' => 'integer',
    ];
}
