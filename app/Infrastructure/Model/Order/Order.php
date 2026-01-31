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

namespace App\Infrastructure\Model\Order;

use App\Domain\Order\Enum\OrderStatus;
use App\Infrastructure\Model\Concerns\LoadsRelations;
use App\Infrastructure\Model\Member\Member;
use Carbon\Carbon;
use Hyperf\Database\Model\Events\Creating;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\Database\Model\Relations\HasOne;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $order_no
 * @property int $member_id
 * @property string $order_type
 * @property string $status
 * @property float $goods_amount
 * @property float $shipping_fee
 * @property float $discount_amount
 * @property float $total_amount
 * @property null|float $pay_amount
 * @property string $pay_status
 * @property null|Carbon $pay_time
 * @property null|string $pay_no
 * @property null|string $pay_method
 * @property null|string $buyer_remark
 * @property null|string $seller_remark
 * @property string $shipping_status
 * @property int $package_count
 * @property null|Carbon $expire_time
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Order extends Model
{
    use LoadsRelations;

    protected ?string $table = 'mall_orders';

    protected array $fillable = [
        'order_no',
        'member_id',
        'order_type',
        'status',
        'goods_amount',
        'shipping_fee',
        'discount_amount',
        'total_amount',
        'pay_amount',
        'pay_status',
        'pay_time',
        'pay_no',
        'pay_method',
        'buyer_remark',
        'seller_remark',
        'shipping_status',
        'package_count',
        'expire_time',
    ];

    protected array $casts = [
        'goods_amount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'pay_amount' => 'decimal:2',
        'pay_time' => 'datetime',
        'expire_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function creating(Creating $event): void
    {
        if (empty($this->order_no)) {
            $this->order_no = self::generateOrderNo();
        }
        if (empty($this->status)) {
            $this->status = OrderStatus::PENDING->value;
        }
        if (empty($this->expire_time)) {
            $this->expire_time = Carbon::now()->addMinutes(30);
        }
    }

    public static function generateOrderNo(): string
    {
        return 'ORD' . date('YmdHis') . mb_str_pad((string) mt_rand(0, 9999), 4, '0', \STR_PAD_LEFT);
    }

    public function items(): HasMany
    {
        $relation = $this->hasMany(OrderItem::class, 'order_id', 'id');
        $relation->select([
            'id',
            'order_id',
            'product_id',
            'sku_id',
            'product_name',
            'sku_name',
            'product_image',
            'unit_price',
            'quantity',
            'total_price',
            'created_at',
        ]);
        return $relation;
    }

    public function logs(): HasMany
    {
        return $this->hasMany(OrderLog::class, 'order_id', 'id');
    }

    public function address(): HasOne
    {
        $relation = $this->hasOne(OrderAddress::class, 'order_id', 'id');
        $relation->select([
            'id',
            'order_id',
            'receiver_name',
            'receiver_phone',
            'province',
            'city',
            'district',
            'detail',
            'full_address',
            'created_at',
        ]);
        return $relation;
    }

    public function member(): BelongsTo
    {
        $relation = $this->belongsTo(Member::class, 'member_id', 'id');
        $relation->select(['id', 'nickname', 'phone']);
        return $relation;
    }

    public function packages(): HasMany
    {
        $relation = $this->hasMany(OrderPackage::class, 'order_id', 'id');
        $relation->select([
            'id',
            'order_id',
            'package_no',
            'express_company',
            'express_no',
            'status',
            'shipped_at',
            'delivered_at',
        ]);
        return $relation;
    }
}
