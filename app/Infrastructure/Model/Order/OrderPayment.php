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

use App\Domain\Order\Enum\PaymentStatus;
use Carbon\Carbon;
use Hyperf\Database\Model\Events\Creating;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $payment_no
 * @property int $order_id
 * @property string $order_no
 * @property int $member_id
 * @property string $payment_method
 * @property float $payment_amount
 * @property float $paid_amount
 * @property float $refund_amount
 * @property string $currency
 * @property string $status
 * @property null|string $third_party_no
 * @property null|array $third_party_response
 * @property null|array $callback_data
 * @property null|Carbon $paid_at
 * @property null|Carbon $expired_at
 * @property null|string $remark
 * @property null|array $extra_data
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class OrderPayment extends Model
{
    protected ?string $table = 'order_payments';

    protected array $fillable = [
        'payment_no',
        'order_id',
        'order_no',
        'member_id',
        'payment_method',
        'payment_amount',
        'paid_amount',
        'refund_amount',
        'currency',
        'status',
        'third_party_no',
        'third_party_response',
        'callback_data',
        'paid_at',
        'expired_at',
        'remark',
        'extra_data',
    ];

    protected array $casts = [
        'order_id' => 'integer',
        'member_id' => 'integer',
        'payment_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'third_party_response' => 'array',
        'callback_data' => 'array',
        'extra_data' => 'array',
        'paid_at' => 'datetime',
        'expired_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function creating(Creating $event): void
    {
        if (! $this->payment_no) {
            $this->payment_no = $this->generatePaymentNo();
        }

        if (! $this->currency) {
            $this->currency = 'CNY';
        }

        if (! $this->status) {
            $this->status = PaymentStatus::PENDING->value;
        }

        if (! $this->expired_at) {
            $this->expired_at = Carbon::now()->addMinutes(30);
        }
    }

    public function generatePaymentNo(): string
    {
        return 'PAY' . date('YmdHis') . mb_str_pad((string) mt_rand(0, 9999), 4, '0', \STR_PAD_LEFT);
    }
}
