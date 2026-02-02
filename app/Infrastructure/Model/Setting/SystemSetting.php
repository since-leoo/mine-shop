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

namespace App\Infrastructure\Model\Setting;

use Carbon\Carbon;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $key
 * @property null|string $value
 * @property string $group
 * @property string $type
 * @property string $label
 * @property null|string $description
 * @property bool $is_sensitive
 * @property null|array $meta
 * @property int $sort
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class SystemSetting extends Model
{
    protected ?string $table = 'settings';

    protected array $fillable = [
        'key',
        'value',
        'group',
        'type',
        'label',
        'description',
        'is_sensitive',
        'meta',
        'sort',
    ];

    protected array $casts = [
        'id' => 'integer',
        'sort' => 'integer',
        'is_sensitive' => 'boolean',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
