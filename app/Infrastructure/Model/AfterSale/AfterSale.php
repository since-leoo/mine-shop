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

namespace App\Infrastructure\Model\AfterSale;

use App\Infrastructure\Model\Order\Order;
use App\Infrastructure\Model\Order\OrderItem;
use App\Infrastructure\Model\Order\OrderPaymentRefund;
use Carbon\Carbon;
use Hyperf\Database\Model\Events\Creating;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $after_sale_no
 * @property int $order_id
 * @property int $order_item_id
 * @property int $member_id
 * @property string $type
 * @property string $status
 * @property string $refund_status
 * @property string $return_status
 * @property int $apply_amount
 * @property int $refund_amount
 * @property int $quantity
 * @property string $reason
 * @property null|string $description
 * @property null|string $reject_reason
 * @property null|array $images
 * @property null|string $buyer_return_logistics_company
 * @property null|string $buyer_return_logistics_no
 * @property null|string $reship_logistics_company
 * @property null|string $reship_logistics_no
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class AfterSale extends Model
{
    protected ?string $table = 'order_trade_after_sale';

    protected array $fillable = [
        'after_sale_no',
        'order_id',
        'order_item_id',
        'member_id',
        'type',
        'status',
        'refund_status',
        'return_status',
        'apply_amount',
        'refund_amount',
        'quantity',
        'reason',
        'description',
        'reject_reason',
        'images',
        'buyer_return_logistics_company',
        'buyer_return_logistics_no',
        'reship_logistics_company',
        'reship_logistics_no',
    ];

    protected array $casts = [
        'order_id' => 'integer',
        'order_item_id' => 'integer',
        'member_id' => 'integer',
        'apply_amount' => 'integer',
        'refund_amount' => 'integer',
        'quantity' => 'integer',
        'images' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function creating(Creating $event): void
    {
        if (empty($this->after_sale_no)) {
            $this->after_sale_no = self::generateAfterSaleNo();
        }
    }

    public static function generateAfterSaleNo(?string $createdAt = null, ?int $id = null): string
    {
        $timestamp = $createdAt ? Carbon::parse($createdAt)->format('YmdHis') : date('YmdHis');
        $suffix = $id !== null
            ? str_pad((string) $id, 4, '0', STR_PAD_LEFT)
            : mb_str_pad((string) mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);

        return 'AS' . $timestamp . $suffix;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id', 'id');
    }

    public function latestRefundRecord(): ?OrderPaymentRefund
    {
        return OrderPaymentRefund::query()
            ->where('extra_data->after_sale_id', (int) $this->id)
            ->orderByDesc('id')
            ->first();
    }
}
