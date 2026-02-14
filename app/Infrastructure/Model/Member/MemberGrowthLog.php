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
 * @property int $member_id 会员ID
 * @property int $before_value 变动前成长值
 * @property int $after_value 变动后成长值
 * @property int $change_amount 变动值（正为增加，负为减少）
 * @property string $source 来源
 * @property null|string $related_type 关联类型
 * @property null|int $related_id 关联ID
 * @property null|string $remark 备注
 * @property Carbon $created_at
 */
class MemberGrowthLog extends Model
{
    public bool $timestamps = false;

    protected ?string $table = 'member_growth_logs';

    protected array $fillable = [
        'member_id',
        'before_value',
        'after_value',
        'change_amount',
        'source',
        'related_type',
        'related_id',
        'remark',
        'created_at',
    ];

    protected array $casts = [
        'member_id' => 'integer',
        'before_value' => 'integer',
        'after_value' => 'integer',
        'change_amount' => 'integer',
        'related_id' => 'integer',
        'created_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id', 'id');
    }
}
