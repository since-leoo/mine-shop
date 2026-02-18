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
 * @property int $new_members
 * @property int $active_members
 * @property int $total_members
 * @property int $paying_members
 */
class StatDailyMembers extends Model
{
    protected ?string $table = 'stat_daily_members';

    protected array $fillable = [
        'date', 'new_members', 'active_members', 'total_members', 'paying_members',
    ];

    protected array $casts = [
        'new_members' => 'integer', 'active_members' => 'integer',
        'total_members' => 'integer', 'paying_members' => 'integer',
    ];
}
