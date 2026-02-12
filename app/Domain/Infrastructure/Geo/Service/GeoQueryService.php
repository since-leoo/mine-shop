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

namespace App\Domain\Infrastructure\Geo\Service;

use App\Infrastructure\Model\Geo\GeoRegion;
use App\Infrastructure\Model\Geo\GeoRegionVersion;
use Hyperf\DbConnection\Db;
use Psr\SimpleCache\CacheInterface;

class GeoQueryService
{
    private const CACHE_KEY = 'geo:pcas:%d';

    private const CACHE_TTL = 86400;

    public function __construct(private readonly CacheInterface $cache) {}

    public function getCascadeTree(): array
    {
        $version = $this->latestVersion();
        if (! $version) {
            throw new \RuntimeException('尚未同步行政区划数据');
        }

        $cacheKey = \sprintf(self::CACHE_KEY, $version->id);
        $items = $this->cache->get($cacheKey);
        if ($items === null) {
            $items = $this->buildTree($version->id);
            $this->cache->set($cacheKey, $items, self::CACHE_TTL);
        }

        return [
            'version' => $version->version,
            'updated_at' => $version->synced_at?->toDateTimeString(),
            'items' => $items,
        ];
    }

    /**
     * @return array{version: null|string, list: array<int, array<string, mixed>>}
     */
    public function search(string $keyword, int $limit = 20): array
    {
        $version = $this->latestVersion();
        if (! $version) {
            return ['version' => null, 'list' => []];
        }

        $keyword = trim($keyword);
        if ($keyword === '') {
            return ['version' => $version->version, 'list' => []];
        }

        $limit = max(1, min(50, $limit));

        $list = GeoRegion::query()
            ->select(['code', 'name', 'level', 'full_name', 'path', 'parent_code'])
            ->where('version_id', $version->id)
            ->where(static function ($query) use ($keyword) {
                $query->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('full_name', 'like', '%' . $keyword . '%')
                    ->orWhere('code', 'like', '%' . $keyword . '%');
            })
            ->orderByRaw('CHAR_LENGTH(path) asc')
            ->limit($limit)
            ->get()
            ->map(static function (GeoRegion $region) {
                return [
                    'code' => $region->code,
                    'name' => $region->name,
                    'level' => $region->level,
                    'full_name' => $region->full_name,
                    'parent_code' => $region->parent_code,
                    'path_codes' => self::extractCodes($region->path),
                ];
            })->toArray();

        return [
            'version' => $version->version,
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

    private function buildTree(int $versionId): array
    {
        $regions = GeoRegion::query()
            ->where('version_id', $versionId)
            ->orderBy('path')
            ->get([
                'code', 'parent_code', 'name', 'level', 'path', 'pinyin', 'abbreviation', 'short_name', 'extra',
            ]);

        $nodes = [];
        $tree = [];

        foreach ($regions as $region) {
            $nodes[$region->code] = [
                'code' => $region->code,
                'value' => $region->code,
                'name' => $region->name,
                'label' => $region->name,
                'level' => $region->level,
                'parent_code' => $region->parent_code,
                'path' => $region->path,
                'short_name' => $region->short_name,
                'pinyin' => $region->pinyin,
                'abbreviation' => $region->abbreviation,
                'children' => [],
            ];

            if ($region->parent_code && isset($nodes[$region->parent_code])) {
                $nodes[$region->parent_code]['children'][] = &$nodes[$region->code];
            } else {
                $tree[] = &$nodes[$region->code];
            }
        }

        return array_map([$this, 'normalizeNode'], $tree);
    }

    private function normalizeNode(array $node): array
    {
        $children = $node['children'] ?? [];
        unset($node['children']);
        $node['path_codes'] = self::extractCodes($node['path'] ?? '');
        if ($children !== []) {
            $node['children'] = array_map([$this, 'normalizeNode'], $children);
        } else {
            unset($node['children']);
        }

        return $node;
    }

    private static function extractCodes(?string $path): array
    {
        if (! $path) {
            return [];
        }

        return array_values(array_filter(explode('|', $path), static fn ($code) => $code !== ''));
    }
}
