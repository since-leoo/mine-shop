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
 * @property null|string $name
 * @property null|string $type
 * @property null|int $value
 * @property null|int $min_amount
 * @property null|int $total_quantity
 * @property null|int $used_quantity
 * @property null|int $per_user_limit
 * @property null|Carbon $start_time
 * @property null|Carbon $end_time
 * @property null|string $status
 * @property null|string $description
 * @property null|Carbon $created_at
 * @property null|Carbon $updated_at
 */
class Coupon extends Model
{
    protected ?string $table = 'coupons';

    protected array $fillable = [
        'name', 'type', 'value', 'min_amount', 'total_quantity', 'used_quantity',
        'per_user_limit', 'start_time', 'end_time', 'status', 'description',
    ];

    protected array $casts = [
        'value' => 'integer', 'min_amount' => 'integer', 'total_quantity' => 'integer',
        'used_quantity' => 'integer', 'per_user_limit' => 'integer',
        'start_time' => 'datetime', 'end_time' => 'datetime',
        'created_at' => 'datetime', 'updated_at' => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(CouponUser::class, 'coupon_id', 'id');
    }
}
