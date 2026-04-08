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
use Carbon\Carbon;
use Hyperf\DbConnection\Db;
use Hyperf\Guzzle\ClientFactory;

use function Hyperf\Coroutine\parallel;

class GeoRegionSyncService
{
    private int $sequence = 0;

    private int $batchSize = 1000;

    private int $chunkSize = 200;

    private int $parallelWorkers = 4;

    public function __construct(private readonly ClientFactory $clientFactory) {}

    /**
     * @param array{source?:string,url?:string,version?:string,released_at?:null|string,force?:bool,dry_run?:bool,parallel_workers?:int,batch_size?:int,chunk_size?:int} $options
     * @return array{version?:string,records?:int,source?:string,url?:string,dry_run?:bool}
     * @throws \JsonException
     */
    public function sync(array $options = []): array
    {
        $this->applyRuntimeOptions($options);
        $source = $options['source'] ?? 'areacity';
        $url = $options['url'] ?? $this->resolveDefaultSourceUrl($source);
        $versionValue = $options['version'] ?? date('Y-m-d');
        $releasedAt = $options['released_at'] ?? null;
        $force = (bool) ($options['force'] ?? false);
        $dryRun = (bool) ($options['dry_run'] ?? false);

        if ($url === null || $url === '') {
            throw new \RuntimeException('No available data source URL found');
        }

        $payload = $this->fetchPayload($url, $source);
        $this->sequence = 0;

        if ($dryRun) {
            $timestamp = Carbon::now()->toDateTimeString();
            $count = 0;
            foreach ($this->iterateUniqueRecords($payload, 0, $timestamp) as $_) {
                ++$count;
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

        return [
            'version' => $versionValue,
            'records' => $inserted,
            'source' => $source,
            'url' => $url,
        ];
    }

    /**
     * @throws \JsonException|\RuntimeException
     */
    private function fetchPayload(string $url, string $source): array
    {
        $client = $this->clientFactory->create();
        $response = $client->get($url, [
            'headers' => ['Accept' => 'application/json,text/csv;q=0.9,*/*;q=0.8'],
            'timeout' => 30,
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Request failed, HTTP ' . $response->getStatusCode());
        }

        $body = (string) $response->getBody();
        if ($source === 'areacity' || str_ends_with(strtolower($url), '.csv')) {
            return $this->parseAreaCityCsvPayload($body);
        }

        $decoded = json_decode($body, true, 512, \JSON_THROW_ON_ERROR);
        if (! \is_array($decoded)) {
            throw new \RuntimeException('Unsupported payload format, expected JSON array');
        }

        return $decoded;
    }

    private function parseAreaCityCsvPayload(string $csv): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($csv));
        if (! \is_array($lines) || $lines === []) {
            throw new \RuntimeException('AreaCity CSV payload is empty');
        }

        $header = str_getcsv((string) array_shift($lines));
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]) ?? $header[0];
        }
        if ($header !== ['id', 'pid', 'deep', 'name', 'pinyin_prefix', 'pinyin', 'ext_id', 'ext_name']) {
            throw new \RuntimeException('AreaCity CSV header format is invalid');
        }

        $nodesById = [];
        $childrenMap = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $columns = str_getcsv($line);
            if (\count($columns) !== 8) {
                continue;
            }

            $id = trim((string) $columns[0]);
            $pid = trim((string) $columns[1]);
            if ($id === '') {
                continue;
            }

            $nodesById[$id] = [
                'id' => $id,
                'pid' => $pid,
                'deep' => (int) $columns[2],
                'name' => trim((string) $columns[3]),
                'pinyin_prefix' => trim((string) $columns[4]),
                'pinyin' => trim((string) $columns[5]),
                'ext_id' => trim((string) $columns[6]),
                'ext_name' => trim((string) $columns[7]),
            ];
            $childrenMap[$pid][] = $id;
        }

        $buildNode = function (string $id) use (&$buildNode, $nodesById, $childrenMap): ?array {
            if (! isset($nodesById[$id])) {
                return null;
            }

            $node = $nodesById[$id];
            $children = [];
            foreach ($childrenMap[$id] ?? [] as $childId) {
                $child = $buildNode((string) $childId);
                if ($child !== null) {
                    $children[] = $child;
                }
            }

            return [
                'code' => $node['ext_id'] !== '' ? $node['ext_id'] : $node['id'],
                'name' => $node['name'],
                'pinyin' => $node['pinyin'] !== '' ? $node['pinyin'] : null,
                'initial' => $node['pinyin_prefix'] !== '' ? $node['pinyin_prefix'] : null,
                'children' => $children,
                'id' => $node['id'],
                'pid' => $node['pid'],
                'deep' => $node['deep'],
                'ext_id' => $node['ext_id'],
                'ext_name' => $node['ext_name'],
            ];
        };

        $rootIds = $childrenMap['0'] ?? [];
        if ($rootIds === []) {
            return [];
        }

        // Build each province subtree concurrently to reduce parse latency on large CSV payloads.
        $tasks = [];
        foreach ($rootIds as $rootId) {
            $tasks[] = static fn () => $buildNode((string) $rootId);
        }

        $results = parallel($tasks, min($this->parallelWorkers, max(1, \count($tasks))));
        return array_values(array_filter($results, static fn ($item) => \is_array($item)));
    }

    private function prepareVersion(array $payload, string $source, ?string $url, string $versionValue, ?string $releasedAt, bool $force): int
    {
        return Db::transaction(static function () use ($payload, $source, $url, $versionValue, $releasedAt, $force) {
            $existing = GeoRegionVersion::query()->where('version', $versionValue)->first();
            if ($existing !== null && ! $force) {
                throw new \RuntimeException(sprintf('Version %s already exists, use --force to overwrite', $versionValue));
            }

            if ($existing !== null) {
                GeoRegion::query()->where('version_id', $existing->id)->delete();
                $existing->delete();
            }

            // `geo_regions.code` is globally unique, so we must clear previous version rows
            // before writing a new version to avoid cross-version unique conflicts.
            GeoRegion::query()->delete();
            GeoRegionVersion::query()->delete();

            $now = Carbon::now();
            $checksum = hash('sha256', json_encode($payload, \JSON_UNESCAPED_UNICODE));

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
        $generator = $this->iterateUniqueRecords($payload, $versionId, $timestamp);
        $buffer = [];
        $inserted = 0;

        foreach ($generator as $record) {
            $buffer[] = $record;
            if (\count($buffer) >= $this->batchSize) {
                $this->insertChunk($buffer);
                $inserted += \count($buffer);
                $buffer = [];
            }
        }

        if ($buffer !== []) {
            $this->insertChunk($buffer);
            $inserted += \count($buffer);
        }

        return $inserted;
    }

    private function iterateUniqueRecords(array $payload, int $versionId, string $timestamp): \Generator
    {
        $seenCodes = [];
        foreach ($this->flattenGenerator($payload, $versionId, null, [], $timestamp) as $record) {
            $code = (string) ($record['code'] ?? '');
            if ($code === '' || isset($seenCodes[$code])) {
                continue;
            }

            $seenCodes[$code] = true;
            yield $record;
        }
    }

    private function applyRuntimeOptions(array $options): void
    {
        $this->parallelWorkers = max(1, (int) ($options['parallel_workers'] ?? $this->parallelWorkers));
        $this->batchSize = max(100, (int) ($options['batch_size'] ?? $this->batchSize));
        $this->chunkSize = max(50, (int) ($options['chunk_size'] ?? $this->chunkSize));
    }

    private function flattenGenerator(array $items, int $versionId, ?string $parentCode, array $ancestors, string $timestamp): \Generator
    {
        foreach ($items as $item) {
            $code = $this->normalizeCode($item['code'] ?? $item['adcode'] ?? $item['value'] ?? null);
            $name = (string) ($item['name'] ?? $item['label'] ?? '');
            if ($code === '' || $name === '') {
                continue;
            }

            $level = $this->resolveLevel(\count($ancestors));
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
                'is_terminal' => empty($item['children']) || ! \is_array($item['children']),
                'is_active' => true,
                'extra' => $extra === null ? null : json_encode($extra, \JSON_UNESCAPED_UNICODE),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];

            if (! empty($item['children']) && \is_array($item['children'])) {
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

        $taskCount = max(1, \count($tasks));
        $concurrency = min($this->parallelWorkers, $taskCount);
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

    private function normalizeCode(int|string|null $code): string
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
            'areacity' => 'https://raw.githubusercontent.com/xiangyuecn/AreaCity-JsSpider-StatsGov/master/src/%E9%87%87%E9%9B%86%E5%88%B0%E7%9A%84%E6%95%B0%E6%8D%AE/ok_data_level4.csv',
            default => null,
        };
    }
}
