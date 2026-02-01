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

namespace App\Infrastructure\Model\Coupon;

use Carbon\Carbon;
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $type
 * @property float $value
 * @property float $min_amount
 * @property int $total_quantity
 * @property int $used_quantity
 * @property int $per_user_limit
 * @property Carbon $start_time
 * @property Carbon $end_time
 * @property string $status
 * @property null|string $description
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Coupon extends Model
{
    protected ?string $table = 'mall_coupons';

    protected array $fillable = [
        'name',
        'type',
        'value',
        'min_amount',
        'total_quantity',
        'used_quantity',
        'per_user_limit',
        'start_time',
        'end_time',
        'status',
        'description',
    ];

    protected array $casts = [
        'value' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'total_quantity' => 'integer',
        'used_quantity' => 'integer',
        'per_user_limit' => 'integer',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(CouponUser::class, 'coupon_id', 'id');
    }
}
