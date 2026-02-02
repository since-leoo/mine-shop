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

namespace App\Infrastructure\Service\Geo;

use App\Infrastructure\Model\Geo\GeoRegion;
use App\Infrastructure\Model\Geo\GeoRegionVersion;
use Carbon\Carbon;
use Generator;
use Hyperf\DbConnection\Db;
use Hyperf\Guzzle\ClientFactory;
use RuntimeException;
use function Hyperf\Coroutine\parallel;

class GeoRegionSyncService
{
    private int $sequence = 0;
    private int $batchSize = 1000;
    private int $chunkSize = 200;
    private int $parallelWorkers = 4;

    public function __construct(private readonly ClientFactory $clientFactory)
    {
    }

    /**
     * @param array{source?:string,url?:string,version?:string,released_at?:string|null,force?:bool,dry_run?:bool} $options
     * @return array{version?:string,records?:int,source?:string,url?:string,dry_run?:bool}
     * @throws \JsonException
     */
    public function sync(array $options = []): array
    {
        $source = $options['source'] ?? 'modood';
        $url = $options['url'] ?? $this->resolveDefaultSourceUrl($source);
        $versionValue = $options['version'] ?? date('Y-m-d');
        $releasedAt = $options['released_at'] ?? null;
        $force = (bool) ($options['force'] ?? false);
        $dryRun = (bool) ($options['dry_run'] ?? false);

        if ($url === null || $url === '') {
            throw new RuntimeException('未找到可用的数据源地址');
        }

        $payload = $this->fetchPayload($url);
        $this->sequence = 0;

        if ($dryRun) {
            $timestamp = Carbon::now()->toDateTimeString();
            $count = 0;
            foreach ($this->flattenGenerator($payload, 0, null, [], $timestamp) as $_) {
                $count++;
            }

            return [
                'dry_run' => true,
                'records' => $count,
                'version' => $versionValue,
                'source' => $source,
                'url' => $url,
            ];
        }

        $versionId = $this->prepareVersion($payload, $source, $url, $versionValue, $releasedAt, $force);

        $timestamp = Carbon::now()->toDateTimeString();
        $inserted = $this->streamInsert($payload, $versionId, $timestamp);

        $version = GeoRegionVersion::query()->find($versionId);
        if ($version !== null) {
            $meta = $version->meta ?? [];
            $meta['records'] = $inserted;
            $version->meta = $meta;
            $version->synced_at = Carbon::now();
            $version->save();
        }

        $summary = [
            'version' => $versionValue,
            'records' => $inserted,
            'source' => $source,
            'url' => $url,
        ];

        return $summary;
    }

    /**
     * @throws \JsonException|RuntimeException
     */
    private function fetchPayload(string $url): array
    {
        $client = $this->clientFactory->create();
        $response = $client->get($url, [
            'headers' => ['Accept' => 'application/json'],
            'timeout' => 30,
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('请求数据源失败，HTTP ' . $response->getStatusCode());
        }

        $body = (string) $response->getBody();
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new RuntimeException('数据源格式不正确，需为JSON数组');
        }

        return $decoded;
    }

    private function prepareVersion(array $payload, string $source, ?string $url, string $versionValue, ?string $releasedAt, bool $force): int
    {
        return Db::transaction(function () use ($payload, $source, $url, $versionValue, $releasedAt, $force) {
            $existing = GeoRegionVersion::query()->where('version', $versionValue)->first();
            if ($existing !== null && ! $force) {
                throw new RuntimeException(sprintf('版本 %s 已存在，如需覆盖请启用 force 选项', $versionValue));
            }

            if ($existing !== null) {
                GeoRegion::query()->where('version_id', $existing->id)->delete();
                $existing->delete();
            }

            $now = Carbon::now();
            $checksum = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE));

            $version = GeoRegionVersion::query()->create([
                'version' => $versionValue,
                'source' => $source,
                'source_url' => $url,
                'checksum' => $checksum,
                'released_at' => $releasedAt,
                'synced_at' => $now,
                'meta' => [
                    'payload_preview_keys' => array_keys((array) ($payload[0] ?? [])),
                ],
            ]);

            return (int) $version->id;
        });
    }

    private function streamInsert(array $payload, int $versionId, string $timestamp): int
    {
        $generator = $this->flattenGenerator($payload, $versionId, null, [], $timestamp);
        $buffer = [];
        $inserted = 0;

        foreach ($generator as $record) {
            $buffer[] = $record;
            if (count($buffer) >= $this->batchSize) {
                $this->insertChunk($buffer);
                $inserted += count($buffer);
                $buffer = [];
            }
        }

        if ($buffer !== []) {
            $this->insertChunk($buffer);
            $inserted += count($buffer);
        }

        return $inserted;
    }

    private function flattenGenerator(array $items, int $versionId, ?string $parentCode, array $ancestors, string $timestamp): Generator
    {
        foreach ($items as $item) {
            $code = $this->normalizeCode($item['code'] ?? $item['adcode'] ?? $item['value'] ?? null);
            $name = (string) ($item['name'] ?? $item['label'] ?? '');
            if ($code === '' || $name === '') {
                continue;
            }

            $level = $this->resolveLevel(count($ancestors));
            $currentAncestors = array_merge($ancestors, [['code' => $code, 'name' => $name]]);
            $path = '|' . implode('|', array_column($currentAncestors, 'code')) . '|';
            $fullName = implode(' / ', array_column($currentAncestors, 'name'));
            $raw = $item;
            unset($raw['children']);
            $extra = $this->normalizeExtra($raw);

            yield [
                'code' => $code,
                'parent_code' => $parentCode,
                'version_id' => $versionId,
                'level' => $level,
                'name' => $name,
                'short_name' => $item['short'] ?? $item['shortName'] ?? null,
                'pinyin' => $item['pinyin'] ?? $item['py'] ?? null,
                'abbreviation' => $item['abbr'] ?? $item['initial'] ?? null,
                'full_name' => $fullName,
                'path' => $path,
                'longitude' => $item['lng'] ?? $item['longitude'] ?? null,
                'latitude' => $item['lat'] ?? $item['latitude'] ?? null,
                'sort_order' => ++$this->sequence,
                'is_terminal' => empty($item['children']) || ! is_array($item['children']),
                'is_active' => true,
                'extra' => $extra === null ? null : json_encode($extra, JSON_UNESCAPED_UNICODE),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];

            if (! empty($item['children']) && is_array($item['children'])) {
                yield from $this->flattenGenerator($item['children'], $versionId, $code, $currentAncestors, $timestamp);
            }
        }
    }

    private function insertChunk(array $chunk): void
    {
        $segments = array_chunk($chunk, $this->chunkSize);
        $tasks = [];

        foreach ($segments as $segment) {
            $tasks[] = static function () use ($segment) {
                GeoRegion::query()->insert($segment);
            };
        }

        if ($tasks === []) {
            return;
        }

        $concurrency = min($this->parallelWorkers, count($tasks) ?: 1);
        parallel($tasks, $concurrency);
    }

    private function resolveLevel(int $depth): string
    {
        return match ($depth) {
            0 => 'province',
            1 => 'city',
            2 => 'district',
            3 => 'street',
            default => 'village',
        };
    }

    private function normalizeCode(null|int|string $code): string
    {
        if ($code === null) {
            return '';
        }

        return trim((string) $code);
    }

    private function normalizeExtra(array $raw): ?array
    {
        $filtered = array_filter($raw, static function ($value) {
            return $value !== null && $value !== '';
        });

        return $filtered === [] ? null : $filtered;
    }

    private function resolveDefaultSourceUrl(string $source): ?string
    {
        return match ($source) {
            'modood' => 'https://raw.githubusercontent.com/modood/Administrative-divisions-of-China/master/dist/pcas-code.json',
            'areacity' => 'https://raw.githubusercontent.com/kakuilan/AreaCity-JsSpider-StatsGov/master/data/pcas-code.json',
            default => null,
        };
    }
}
