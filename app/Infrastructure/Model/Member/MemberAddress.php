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
 * @property int $member_id
 * @property string $receiver_name
 * @property string $receiver_phone
 * @property string $province
 * @property string $city
 * @property string $district
 * @property string $detail
 * @property string $full_address
 * @property bool $is_default
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class MemberAddress extends Model
{
    protected ?string $table = 'member_addresses';

    protected array $fillable = [
        'member_id',
        'receiver_name',
        'receiver_phone',
        'province',
        'city',
        'district',
        'detail',
        'full_address',
        'is_default',
    ];

    protected array $casts = [
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id', 'id');
    }
}
