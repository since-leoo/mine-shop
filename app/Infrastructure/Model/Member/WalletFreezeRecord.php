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
 * @property int $id
 * @property string $freeze_no 冻结单号
 * @property int $wallet_id 钱包ID
 * @property int $member_id 会员ID
 * @property int $freeze_amount 申请冻结金额
 * @property int $frozen_amount 实际冻结金额
 * @property int $released_amount 已释放金额
 * @property string $status 状态
 * @property null|string $freeze_reason 冻结原因
 * @property null|string $release_reason 释放原因
 * @property null|string $related_type 关联类型
 * @property null|int $related_id 关联ID
 * @property null|string $related_no 关联单号
 * @property string $operator_type 操作员类型
 * @property null|int $operator_id 操作员ID
 * @property null|string $operator_name 操作员名称
 * @property null|Carbon $frozen_at 冻结时间
 * @property null|Carbon $released_at 释放时间
 * @property null|Carbon $expired_at 过期时间
 * @property null|string $remark 备注
 * @property null|array $extra_data 额外数据
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class WalletFreezeRecord extends Model
{
    protected ?string $table = 'wallet_freeze_records';

    protected array $fillable = [
        'freeze_no',
        'wallet_id',
        'member_id',
        'freeze_amount',
        'frozen_amount',
        'released_amount',
        'status',
        'freeze_reason',
        'release_reason',
        'related_type',
        'related_id',
        'related_no',
        'operator_type',
        'operator_id',
        'operator_name',
        'frozen_at',
        'released_at',
        'expired_at',
        'remark',
        'extra_data',
    ];

    protected array $casts = [
        'freeze_amount' => 'integer',
        'frozen_amount' => 'integer',
        'released_amount' => 'integer',
        'extra_data' => 'json',
        'frozen_at' => 'datetime',
        'released_at' => 'datetime',
        'expired_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(MemberWallet::class, 'wallet_id', 'id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id', 'id');
    }
}
