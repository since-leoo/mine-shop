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

namespace App\Infrastructure\Model\Statistics;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $date
 * @property string $payment_method
 * @property int $pay_count
 * @property int $pay_amount
 * @property int $refund_count
 * @property int $refund_amount
 */
class StatDailyPayments extends Model
{
    protected ?string $table = 'stat_daily_payments';

    protected array $fillable = [
        'date', 'payment_method', 'pay_count', 'pay_amount', 'refund_count', 'refund_amount',
    ];

    protected array $casts = [
        'pay_count' => 'integer', 'pay_amount' => 'integer',
        'refund_count' => 'integer', 'refund_amount' => 'integer',
    ];
}
