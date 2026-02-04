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

namespace App\Infrastructure\Model\Geo;

use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $code
 * @property null|string $parent_code
 * @property int $version_id
 * @property string $level
 * @property string $name
 * @property null|string $short_name
 * @property null|string $pinyin
 * @property null|string $abbreviation
 * @property null|string $full_name
 * @property null|string $path
 * @property null|float $longitude
 * @property null|float $latitude
 * @property int $sort_order
 * @property bool $is_terminal
 * @property bool $is_active
 * @property null|array $extra
 */
class GeoRegion extends Model
{
    protected ?string $table = 'geo_regions';

    protected array $fillable = [
        'code',
        'parent_code',
        'version_id',
        'level',
        'name',
        'short_name',
        'pinyin',
        'abbreviation',
        'full_name',
        'path',
        'longitude',
        'latitude',
        'sort_order',
        'is_terminal',
        'is_active',
        'extra',
    ];

    protected array $casts = [
        'longitude' => 'float',
        'latitude' => 'float',
        'sort_order' => 'integer',
        'is_terminal' => 'boolean',
        'is_active' => 'boolean',
        'extra' => 'array',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(GeoRegionVersion::class, 'version_id', 'id');
    }
}
