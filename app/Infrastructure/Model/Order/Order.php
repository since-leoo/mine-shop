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

use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Enum\OrderStatus;
use App\Infrastructure\Model\Concerns\LoadsRelations;
use App\Infrastructure\Model\Member\Member;
use Carbon\Carbon;
use Hyperf\Database\Model\Builder;
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
 * @property int $goods_amount
 * @property int $shipping_fee
 * @property int $discount_amount
 * @property int $total_amount
 * @property null|int $pay_amount
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
 * @method Builder pendingStatus() 获取待付款订单
 * @method Builder paidStatus() 获取已付款订单
 * @method Builder shippedStatus() 获取已发货订单
 * @method Builder completedStatus() 获取已完成订单
 * @method Builder afterSaleStatus() 获取售后订单
 */
class Order extends Model
{
    use LoadsRelations;

    protected ?string $table = 'orders';

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
        'goods_amount' => 'integer',
        'shipping_fee' => 'integer',
        'discount_amount' => 'integer',
        'total_amount' => 'integer',
        'pay_amount' => 'integer',
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
            'name',
            'phone',
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

    public function scopePendingStatus()
    {
        return self::where('status', OrderStatus::PENDING->value);
    }

    public function scopePaidStatus()
    {
        return self::where('status', OrderStatus::PAID->value);
    }

    public function scopeShippedStatus()
    {
        return self::whereIn('status', [
            OrderStatus::PARTIAL_SHIPPED->value,
            OrderStatus::SHIPPED->value,
        ]);
    }

    public function scopeCompletedStatus()
    {
        return self::where('status', OrderStatus::COMPLETED->value);
    }

    public function scopeAfterSaleStatus()
    {
        return self::whereIn('status', [
            OrderStatus::REFUNDED->value,
            OrderStatus::CANCELLED->value,
        ]);
    }

    public function ship(OrderEntity $entity): void
    {
        $this->status = $entity->getStatus();
        $this->shipping_status = $entity->getShippingStatus();
        $this->package_count = $entity->getPackageCount();
        $this->save();
    }

    public function cancel(OrderEntity $entity): void
    {
        $this->status = $entity->getStatus();
        $this->shipping_status = $entity->getShippingStatus();
        $this->package_count = $entity->getPackageCount();
        $this->save();
    }

    public function paid(OrderEntity $entity): void
    {
        $this->status = $entity->getStatus();
        $this->pay_status = $entity->getPayStatus();
        $this->pay_no = $entity->getPayNo();
        $this->pay_time = $entity->getPayTime();
        $this->pay_method = $entity->getPayMethod();
        $this->save();
    }

    public function complete(OrderEntity $entity): void
    {
        $this->status = $entity->getStatus();
        $this->shipping_status = $entity->getShippingStatus();
        $this->save();
    }
}
