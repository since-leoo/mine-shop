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
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property int $sort
 * @property bool $is_enabled
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property null|Carbon $deleted_at
 */
class DiyTemplateCategory extends Model
{
    use SoftDeletes;

    protected ?string $table = 'diy_template_categories';

    protected array $fillable = [
        'name',
        'code',
        'sort',
        'is_enabled',
    ];

    protected array $casts = [
        'id' => 'integer',
        'sort' => 'integer',
        'is_enabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function templates(): HasMany
    {
        return $this->hasMany(DiyTemplate::class, 'category_id');
    }
}
