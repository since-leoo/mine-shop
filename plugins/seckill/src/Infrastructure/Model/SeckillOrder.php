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

namespace Plugin\Since\Seckill\Infrastructure\Model;

use App\Infrastructure\Model\Member\Member;
use App\Infrastructure\Model\Order\Order;
use Carbon\Carbon;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property int $order_id
 * @property int $activity_id
 * @property int $session_id
 * @property int $seckill_product_id
 * @property int $member_id
 * @property int $product_id
 * @property int $product_sku_id
 * @property int $quantity
 * @property int $original_price
 * @property int $seckill_price
 * @property int $total_amount
 * @property string $status
 * @property null|Carbon $seckill_time
 * @property null|Carbon $pay_time
 * @property null|Carbon $cancel_time
 * @property null|string $remark
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class SeckillOrder extends Model
{
    protected ?string $table = 'seckill_session_orders';

    protected array $fillable = [
        'order_id', 'activity_id', 'session_id', 'seckill_product_id', 'member_id',
        'product_id', 'product_sku_id', 'quantity', 'original_price', 'seckill_price',
        'total_amount', 'status', 'seckill_time', 'pay_time', 'cancel_time', 'remark',
    ];

    protected array $casts = [
        'order_id' => 'integer', 'activity_id' => 'integer', 'session_id' => 'integer',
        'seckill_product_id' => 'integer', 'member_id' => 'integer',
        'product_id' => 'integer', 'product_sku_id' => 'integer',
        'quantity' => 'integer', 'original_price' => 'integer',
        'seckill_price' => 'integer', 'total_amount' => 'integer',
        'seckill_time' => 'datetime', 'pay_time' => 'datetime',
        'cancel_time' => 'datetime', 'created_at' => 'datetime', 'updated_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(SeckillActivity::class, 'activity_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(SeckillSession::class, 'session_id');
    }
}
