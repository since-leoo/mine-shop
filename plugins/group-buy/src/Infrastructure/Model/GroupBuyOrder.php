<?php

declare(strict_types=1);

namespace Plugin\Since\GroupBuy\Infrastructure\Model;

use App\Infrastructure\Model\Member\Member;
use App\Infrastructure\Model\Order\Order;
use Carbon\Carbon;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property int $group_buy_id
 * @property int $order_id
 * @property int $member_id
 * @property string $group_no
 * @property bool $is_leader
 * @property int $quantity
 * @property int $original_price
 * @property int $group_price
 * @property int $total_amount
 * @property string $status
 * @property Carbon $join_time
 * @property null|Carbon $group_time
 * @property null|Carbon $pay_time
 * @property null|Carbon $cancel_time
 * @property null|Carbon $expire_time
 * @property null|string $share_code
 * @property null|int $parent_order_id
 * @property null|string $remark
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class GroupBuyOrder extends Model
{
    protected ?string $table = 'group_buy_orders';

    protected array $fillable = [
        'group_buy_id', 'order_id', 'member_id', 'group_no', 'is_leader',
        'quantity', 'original_price', 'group_price', 'total_amount', 'status',
        'join_time', 'group_time', 'pay_time', 'cancel_time', 'expire_time',
        'share_code', 'parent_order_id', 'remark',
    ];

    protected array $casts = [
        'group_buy_id' => 'integer', 'order_id' => 'integer', 'member_id' => 'integer',
        'is_leader' => 'boolean', 'quantity' => 'integer',
        'original_price' => 'integer', 'group_price' => 'integer', 'total_amount' => 'integer',
        'parent_order_id' => 'integer',
        'join_time' => 'datetime', 'group_time' => 'datetime', 'pay_time' => 'datetime',
        'cancel_time' => 'datetime', 'expire_time' => 'datetime',
        'created_at' => 'datetime', 'updated_at' => 'datetime',
    ];

    public function groupBuy(): BelongsTo
    {
        return $this->belongsTo(GroupBuy::class, 'group_buy_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}
