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
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property string $page_key
 * @property string $page_type
 * @property null|string $cover
 * @property null|string $description
 * @property array $schema
 * @property int $sort
 * @property bool $is_enabled
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property null|Carbon $deleted_at
 */
class DiyTemplate extends Model
{
    use SoftDeletes;

    protected ?string $table = 'diy_templates';

    protected array $fillable = [
        'category_id',
        'name',
        'page_key',
        'page_type',
        'cover',
        'description',
        'schema',
        'sort',
        'is_enabled',
    ];

    protected array $casts = [
        'id' => 'integer',
        'category_id' => 'integer',
        'schema' => 'array',
        'sort' => 'integer',
        'is_enabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(DiyTemplateCategory::class, 'category_id');
    }
}
