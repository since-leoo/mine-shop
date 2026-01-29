<?php

declare(strict_types=1);

namespace App\Domain\Product\Service;

use App\Domain\Product\Contract\ProductSnapshotInterface;
use App\Infrastructure\Model\Product\Product;
use App\Infrastructure\Model\Product\ProductSku;
use Hyperf\Redis\Redis;
use Hyperf\Redis\RedisFactory;

final class ProductSnapshotService implements ProductSnapshotInterface
{
    private const SKU_SNAPSHOT_KEY = 'mall:snapshot:sku:%d';

    public function __construct(
        private readonly RedisFactory $redisFactory,
        private readonly ProductSku $productSkuModel
    ) {}

    /**
     * @param array<int, int> $skuIds
     * @return array<int, array<string, mixed>>
     */
    public function getSkuSnapshots(array $skuIds): array
    {
        $skuIds = array_values(array_filter(array_unique(array_map('intval', $skuIds))));
        if ($skuIds === []) {
            return [];
        }

        $keys = array_map(fn (int $id) => $this->skuKey($id), $skuIds);
        $rawValues = $this->redis()->mget($keys);
        if (! is_array($rawValues)) {
            $rawValues = array_fill(0, \count($keys), null);
        }

        $snapshots = [];
        $missing = [];

        foreach ($skuIds as $index => $skuId) {
            $raw = $rawValues[$index] ?? null;
            if (! $raw) {
                $missing[] = $skuId;
                continue;
            }
            $snapshot = $this->decodeSnapshot((string) $raw);
            if ($snapshot === null) {
                $missing[] = $skuId;
                continue;
            }
            $snapshots[$skuId] = $snapshot;
        }

        if ($missing !== []) {
            $loaded = $this->loadSnapshots($missing);
            foreach ($loaded as $id => $payload) {
                $snapshots[$id] = $payload;
            }
        }

        return $snapshots;
    }

    public function syncProduct(Product $product): void
    {
        $product->loadMissing('skus');
        foreach ($product->skus as $sku) {
            if ($sku instanceof ProductSku) {
                $this->storeSnapshot($product, $sku);
            }
        }
    }

    /**
     * @param array<int, int> $skuIds
     */
    public function deleteSkus(array $skuIds): void
    {
        $keys = array_map(fn (int $id) => $this->skuKey($id), array_filter(array_map('intval', $skuIds)));
        if ($keys === []) {
            return;
        }

        $this->redis()->del(...$keys);
    }

    /**
     * @param array<int, int> $skuIds
     * @return array<int, array<string, mixed>>
     */
    private function loadSnapshots(array $skuIds): array
    {
        $models = $this->productSkuModel->newQuery()
            ->with('product')
            ->whereIn('id', $skuIds)
            ->get();

        $results = [];
        foreach ($models as $sku) {
            if (! $sku instanceof ProductSku || ! $sku->product instanceof Product) {
                continue;
            }
            $payload = $this->buildSnapshot($sku->product, $sku);
            $results[(int) $sku->id] = $payload;
            $this->persistSnapshot((int) $sku->id, $payload);
        }

        return $results;
    }

    private function storeSnapshot(Product $product, ProductSku $sku): void
    {
        $payload = $this->buildSnapshot($product, $sku);
        $this->persistSnapshot((int) $sku->id, $payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSnapshot(Product $product, ProductSku $sku): array
    {
        return [
            'product_id' => (int) $product->id,
            'product_code' => (string) $product->product_code,
            'product_name' => (string) $product->name,
            'product_status' => (string) $product->status,
            'product_image' => $product->main_image,
            'product_min_price' => (float) $product->min_price,
            'product_max_price' => (float) $product->max_price,
            'sku_id' => (int) $sku->id,
            'sku_code' => (string) $sku->sku_code,
            'sku_name' => (string) $sku->sku_name,
            'sku_status' => (string) $sku->status,
            'sku_image' => $sku->image,
            'spec_values' => $sku->spec_values ?? [],
            'sale_price' => (float) $sku->sale_price,
            'market_price' => (float) $sku->market_price,
            'cost_price' => (float) $sku->cost_price,
            'weight' => (float) $sku->weight,
            'warning_stock' => (int) $sku->warning_stock,
        ];
    }

    private function persistSnapshot(int $skuId, array $payload): void
    {
        $encoded = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
        $this->redis()->set($this->skuKey($skuId), $encoded);
    }

    private function decodeSnapshot(string $raw): ?array
    {
        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            return $decoded;
        } catch (\JsonException) {
            return null;
        }
    }

    private function skuKey(int $skuId): string
    {
        return sprintf(self::SKU_SNAPSHOT_KEY, $skuId);
    }

    private function redis(): Redis
    {
        return $this->redisFactory->get('default');
    }
}
