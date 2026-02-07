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

use Carbon\Carbon;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $refund_no
 * @property int $payment_id
 * @property string $payment_no
 * @property int $order_id
 * @property string $order_no
 * @property int $member_id
 * @property int $refund_amount
 * @property null|string $refund_reason
 * @property string $status
 * @property null|string $third_party_refund_no
 * @property null|array $third_party_response
 * @property null|Carbon $processed_at
 * @property string $operator_type
 * @property null|int $operator_id
 * @property null|string $operator_name
 * @property null|string $remark
 * @property null|array $extra_data
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class OrderPaymentRefund extends Model
{
    protected ?string $table = 'payment_refunds';

    protected array $fillable = [
        'refund_no',
        'payment_id',
        'payment_no',
        'order_id',
        'order_no',
        'member_id',
        'refund_amount',
        'refund_reason',
        'status',
        'third_party_refund_no',
        'third_party_response',
        'processed_at',
        'operator_type',
        'operator_id',
        'operator_name',
        'remark',
        'extra_data',
    ];

    protected array $casts = [
        'payment_id' => 'integer',
        'order_id' => 'integer',
        'member_id' => 'integer',
        'refund_amount' => 'integer',
        'third_party_response' => 'array',
        'processed_at' => 'datetime',
        'operator_id' => 'integer',
        'extra_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
