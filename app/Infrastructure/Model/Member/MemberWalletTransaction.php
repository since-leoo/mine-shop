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
use Hyperf\Database\Model\Events\Creating;
use Hyperf\Database\Model\Events\Updating;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property null|int $wallet_id
 * @property int $member_id
 * @property string $wallet_type
 * @property string $transaction_no
 * @property string $type
 * @property float $amount
 * @property float $balance_before
 * @property float $balance_after
 * @property null|string $source
 * @property null|string $related_type
 * @property null|int $related_id
 * @property null|string $description
 * @property null|string $remark
 * @property string $operator_type
 * @property null|int $operator_id
 * @property null|string $operator_name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class MemberWalletTransaction extends Model
{
    protected ?string $table = 'wallet_transactions';

    protected array $fillable = [
        'wallet_id',
        'member_id',
        'wallet_type',
        'transaction_no',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'source',
        'related_type',
        'related_id',
        'description',
        'remark',
        'operator_type',
        'operator_id',
        'operator_name',
    ];

    protected array $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function creating(Creating $event)
    {
        if (empty($this->wallet_id)) {
            $this->wallet_id = time();
        }
    }

    public function updating(Updating $event)
    {
        if (empty($this->wallet_id)) {
            // 从1000000000开始生成
            $this->wallet_id = time();
        }
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id', 'id');
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(MemberWallet::class, 'wallet_id', 'id');
    }
}
