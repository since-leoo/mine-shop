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
 * @property string $province
 * @property int $order_count
 * @property int $order_amount
 */
class StatDailyRegions extends Model
{
    protected ?string $table = 'stat_daily_regions';

    protected array $fillable = ['date', 'province', 'order_count', 'order_amount'];

    protected array $casts = ['order_count' => 'integer', 'order_amount' => 'integer'];
}
