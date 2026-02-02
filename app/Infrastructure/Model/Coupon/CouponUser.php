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

use App\Infrastructure\Model\Member\Member;
use App\Infrastructure\Model\Order\Order;
use Carbon\Carbon;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property int $coupon_id
 * @property int $member_id
 * @property null|int $order_id
 * @property string $status
 * @property Carbon $received_at
 * @property null|Carbon $used_at
 * @property Carbon $expire_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class CouponUser extends Model
{
    protected ?string $table = 'coupon_users';

    protected array $fillable = [
        'coupon_id',
        'member_id',
        'order_id',
        'status',
        'received_at',
        'used_at',
        'expire_at',
    ];

    protected array $casts = [
        'coupon_id' => 'integer',
        'member_id' => 'integer',
        'order_id' => 'integer',
        'received_at' => 'datetime',
        'used_at' => 'datetime',
        'expire_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'coupon_id', 'id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id', 'id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
