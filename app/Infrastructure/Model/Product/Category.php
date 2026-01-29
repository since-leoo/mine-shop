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

namespace App\Infrastructure\Model\Product;

use App\Domain\Product\Enum\CategoryStatus;
use Carbon\Carbon;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property int $parent_id
 * @property string $name
 * @property null|string $icon
 * @property null|string $description
 * @property int $sort
 * @property int $level
 * @property string $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Collection<int,self>|self[] $children
 */
class Category extends Model
{
    public const MAX_LEVEL = 3;

    protected ?string $table = 'mall_categories';

    protected array $fillable = [
        'parent_id',
        'name',
        'icon',
        'description',
        'sort',
        'level',
        'status',
    ];

    protected array $casts = [
        'parent_id' => 'integer',
        'sort' => 'integer',
        'level' => 'integer',
        'status' => 'string',
    ];

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id', 'id')
            ->where('status', CategoryStatus::ACTIVE->value)
            ->orderBy('sort', 'asc');
    }

    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }

    public function parents(): Collection
    {
        $parents = new Collection();
        $parent = $this->parent;

        while ($parent) {
            $parents->prepend($parent);
            $parent = $parent->parent;
        }

        return $parents;
    }

    public function isRoot(): bool
    {
        return $this->parent_id === 0;
    }

    public function isLeaf(): bool
    {
        return $this->children()->count() === 0;
    }

    public function getPath(): string
    {
        $parents = $this->parents();
        $parents->push($this);

        return $parents->pluck('name')->implode(' > ');
    }

    public function validateLevel(): bool
    {
        return $this->level <= self::MAX_LEVEL;
    }

    public static function getNextSort(int $parentId = 0): int
    {
        return self::where('parent_id', $parentId)->max('sort') + 1;
    }

    public function scopeActive($query)
    {
        return $query->where('status', CategoryStatus::ACTIVE->value);
    }

    public function scopeRoot($query)
    {
        return $query->where('parent_id', 0);
    }

    public function scopeByLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    public function scopeOrdered($query, string $direction = 'asc')
    {
        return $query->orderBy('sort', $direction)->orderBy('id', $direction);
    }

    public static function getTree(int $parentId = 0): Collection
    {
        return self::where('parent_id', $parentId)
            ->active()
            ->ordered()
            ->with(['allChildren' => static function ($query) {
                $query->active()->ordered();
            }])
            ->get();
    }

    public static function getOptions(int $excludeId = 0): array
    {
        $categories = self::active()->ordered()->get();
        $options = [];

        foreach ($categories as $category) {
            if ($category->id === $excludeId) {
                continue;
            }

            $prefix = str_repeat('  ', ($category->level - 1) * 2);
            $options[] = [
                'value' => $category->id,
                'label' => $prefix . $category->name,
                'level' => $category->level,
                'disabled' => false,
            ];
        }

        return $options;
    }

    public function canDelete(): bool
    {
        if ($this->children()->count() > 0) {
            return false;
        }

        return true;
    }

    /**
     * @return array<int, array{id: int, name: string, level: int}>
     */
    public function getBreadcrumb(): array
    {
        $breadcrumb = [];
        $parents = $this->parents();
        $parents->push($this);

        foreach ($parents as $category) {
            $breadcrumb[] = [
                'id' => $category->id,
                'name' => $category->name,
                'level' => $category->level,
            ];
        }

        return $breadcrumb;
    }
}
