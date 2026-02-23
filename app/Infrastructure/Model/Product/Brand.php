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

use Carbon\Carbon;
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $name
 * @property null|string $logo
 * @property null|string $description
 * @property null|string $website
 * @property int $sort
 * @property string $status
 * @property null|int $created_by
 * @property null|int $updated_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property null|Carbon $deleted_at
 */
class Brand extends Model
{
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected ?string $table = 'brands';

    protected array $fillable = [
        'name',
        'logo',
        'description',
        'website',
        'sort',
        'status',
        'created_by',
        'updated_by',
    ];

    protected array $casts = [
        'id' => 'integer',
        'sort' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => '启用',
            self::STATUS_INACTIVE => '禁用',
        ];
    }

    public function getStatusTextAttribute(): string
    {
        return self::getStatusOptions()[$this->status] ?? '未知';
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeOrdered($query, string $direction = 'asc')
    {
        return $query->orderBy('sort', $direction)->orderBy('id', $direction);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'brand_id');
    }

    public function getProductCountAttribute(): int
    {
        return $this->products()->count();
    }

    public static function getNextSort(): int
    {
        return (int) self::max('sort') + 1;
    }

    public static function getOptions(): array
    {
        $brands = self::active()->ordered()->get();
        $options = [];

        foreach ($brands as $brand) {
            $options[] = [
                'value' => $brand->id,
                'label' => $brand->name,
            ];
        }

        return $options;
    }

    public function canDelete(): bool
    {
        if (class_exists(Product::class)) {
            return $this->products()->count() === 0;
        }

        return true;
    }
}
