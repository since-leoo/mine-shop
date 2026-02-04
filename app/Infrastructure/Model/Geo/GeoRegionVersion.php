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

use Carbon\Carbon;
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $version
 * @property string $source
 * @property null|string $source_url
 * @property null|string $checksum
 * @property null|Carbon $released_at
 * @property null|Carbon $synced_at
 * @property null|array $meta
 */
class GeoRegionVersion extends Model
{
    protected ?string $table = 'geo_region_versions';

    protected array $fillable = [
        'version',
        'source',
        'source_url',
        'checksum',
        'released_at',
        'synced_at',
        'meta',
    ];

    protected array $casts = [
        'meta' => 'array',
        'released_at' => 'date',
        'synced_at' => 'datetime',
    ];

    public function regions(): HasMany
    {
        return $this->hasMany(GeoRegion::class, 'version_id', 'id');
    }
}
