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

namespace App\Infrastructure\Model\Member;

use Carbon\Carbon;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 自增ID
 * @property int $member_id 会员ID
 * @property string $type 钱包类型
 * @property float $balance 账户余额
 * @property float $frozen_balance 冻结金额
 * @property float $total_recharge 累计充值金额
 * @property float $total_consume 累计消费金额
 * @property null|string $pay_password 密码
 * @property string $status 状态
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class MemberWallet extends Model
{
    protected ?string $table = 'wallets';

    protected array $fillable = [
        'member_id',
        'type',
        'balance',
        'frozen_balance',
        'total_recharge',
        'total_consume',
        'pay_password',
        'status',
    ];

    protected array $casts = [
        'type' => 'string',
        'balance' => 'decimal:2',
        'frozen_balance' => 'decimal:2',
        'total_recharge' => 'decimal:2',
        'total_consume' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id', 'id');
    }
}
