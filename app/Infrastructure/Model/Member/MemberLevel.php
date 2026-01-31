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

namespace App\Infrastructure\Model\Member;

use Carbon\Carbon;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $name
 * @property int $level
 * @property int $growth_value_min
 * @property null|int $growth_value_max
 * @property float $discount_rate
 * @property float $point_rate
 * @property null|array $privileges
 * @property null|string $icon
 * @property null|string $color
 * @property string $status
 * @property int $sort_order
 * @property null|string $description
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class MemberLevel extends Model
{
    protected ?string $table = 'mall_member_levels';

    protected array $fillable = [
        'name',
        'level',
        'growth_value_min',
        'growth_value_max',
        'discount_rate',
        'point_rate',
        'privileges',
        'icon',
        'color',
        'status',
        'sort_order',
        'description',
    ];

    protected array $casts = [
        'growth_value_min' => 'integer',
        'growth_value_max' => 'integer',
        'discount_rate' => 'decimal:2',
        'point_rate' => 'decimal:2',
        'privileges' => 'array',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
