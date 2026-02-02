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
use Hyperf\Database\Model\Relations\BelongsToMany;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $name
 * @property null|string $color
 * @property null|string $description
 * @property string $status
 * @property int $sort_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class MemberTag extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected ?string $table = 'member_tags';

    protected array $fillable = [
        'name',
        'color',
        'description',
        'status',
        'sort_order',
    ];

    protected array $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'mall_member_tag_relations', 'tag_id', 'member_id')
            ->withTimestamps();
    }
}
