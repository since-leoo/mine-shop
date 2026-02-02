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
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property int $member_id
 * @property int $tag_id
 * @property null|int $operator_id
 * @property null|string $operator_name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class MemberTagRelation extends Model
{
    protected ?string $table = 'member_tag_relations';

    protected array $fillable = [
        'member_id',
        'tag_id',
        'operator_id',
        'operator_name',
    ];

    protected array $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
