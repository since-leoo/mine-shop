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

namespace App\Domain\Infrastructure\Geo\Api\Query;

use App\Infrastructure\Model\Geo\GeoRegion;
use App\Infrastructure\Model\Geo\GeoRegionVersion;
use Hyperf\DbConnection\Db;

final class DomainApiGeoRegionQueryService
{

    /**
     * @return array{version: null|string, updated_at: null|string, parent_code: null|string, list: array<int, array<string, mixed>>}
     */
    public function children(?string $parentCode, int $limit = 200): array
    {
        $version = $this->latestVersion();
        if ($version === null) {
            return [
                'version' => null,
                'updated_at' => null,
                'parent_code' => $parentCode,
                'list' => [],
            ];
        }

        $normalizedParentCode = $this->normalizeParentCode($parentCode);
        $limit = max(1, min(1000, $limit));

        $query = GeoRegion::query()
            ->where('version_id', $version->id)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->limit($limit);

        if ($normalizedParentCode === null) {
            $query->whereNull('parent_code');
        } else {
            $query->where('parent_code', $normalizedParentCode);
        }

        $list = $query->get([
            'code', 'parent_code', 'name', 'level', 'full_name', 'path', 'is_terminal', 'short_name', 'pinyin', 'abbreviation',
        ])->map(static function (GeoRegion $region) {
            return [
                'code' => $region->code,
                'parent_code' => $region->parent_code,
                'name' => $region->name,
                'level' => $region->level,
                'full_name' => $region->full_name,
                'path_codes' => self::extractCodes($region->path),
                'is_terminal' => (bool) $region->is_terminal,
                'short_name' => $region->short_name,
                'pinyin' => $region->pinyin,
                'abbreviation' => $region->abbreviation,
            ];
        })->toArray();

        return [
            'version' => $version->version,
            'updated_at' => $version->synced_at?->toDateTimeString(),
            'parent_code' => $normalizedParentCode,
            'list' => $list,
        ];
    }

    private function latestVersion(): ?GeoRegionVersion
    {
        return GeoRegionVersion::query()
            ->orderByDesc(Db::raw('COALESCE(released_at, synced_at)'))
            ->orderByDesc('id')
            ->first();
    }

    private function normalizeParentCode(?string $parentCode): ?string
    {
        $value = trim((string) $parentCode);
        if ($value === '' || $value === '0') {
            return null;
        }

        return $value;
    }

    /**
     * @return array<int, string>
     */
    private static function extractCodes(?string $path): array
    {
        if (! $path) {
            return [];
        }

        return array_values(array_filter(explode('|', $path), static fn ($code) => $code !== ''));
    }
}
