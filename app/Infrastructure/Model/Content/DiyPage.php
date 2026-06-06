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

namespace App\Infrastructure\Model\Content;

use Carbon\Carbon;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $page_key
 * @property string $title
 * @property string $page_type
 * @property null|string $description
 * @property bool $is_enabled
 * @property string $status
 * @property null|int $published_version_id
 * @property null|int $created_by
 * @property null|int $updated_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property null|Carbon $deleted_at
 */
class DiyPage extends Model
{
    use SoftDeletes;

    protected ?string $table = 'diy_pages';

    protected array $fillable = [
        'page_key',
        'title',
        'page_type',
        'description',
        'is_enabled',
        'status',
        'published_version_id',
        'created_by',
        'updated_by',
    ];

    protected array $casts = [
        'id' => 'integer',
        'is_enabled' => 'boolean',
        'published_version_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(DiyPageVersion::class, 'page_id');
    }

    public function publishedVersion(): BelongsTo
    {
        return $this->belongsTo(DiyPageVersion::class, 'published_version_id');
    }
}
