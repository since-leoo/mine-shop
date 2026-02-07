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

namespace App\Domain\Product\Service;

use App\Domain\Product\Contract\ProductSnapshotInterface;
use App\Infrastructure\Abstract\ICache;
use App\Infrastructure\Model\Product\Product;
use App\Infrastructure\Model\Product\ProductSku;
use Hyperf\Codec\Json;

/**
 * 商品缓存服务类
 * 提供商品和SKU快照的缓存管理功能.
 */
final class ProductSnapshotService implements ProductSnapshotInterface
{
    private const CACHE_PREFIX = 'product';

    private const PRODUCT_KEY = 'spu:%d';

    private const SKU_KEY = 'sku:%d';

    private const DEFAULT_WITH = ['skus', 'attributes', 'gallery'];

    /**
     * 构造函数.
     *
     * @param ICache $cache 缓存实例
     * @param Product $productModel 商品模型实例
     * @param ProductSku $productSkuModel 商品SKU模型实例
     */
    public function __construct(
        private readonly ICache $cache,
        private readonly Product $productModel,
        private readonly ProductSku $productSkuModel
    ) {
        $this->cache->setPrefix(self::CACHE_PREFIX);
    }

    /**
     * 获取商品信息.
     *
     * @param int $productId 商品ID
     * @param array $with 关联查询字段
     * @return null|array 商品数据数组或null
     */
    public function getProduct(int $productId, array $with = []): ?array
    {
        if ($productId <= 0) {
            return null;
        }

        $relations = $this->normalizeRelations($with);
        $cached = $this->fetchProductFromCache($productId);

        // 检查缓存中是否包含所需的关系数据
        if ($cached !== null && $this->containsRelations($cached, $relations)) {
            return $cached;
        }

        return $this->rememberProductById($productId, $relations);
    }

    /**
     * 获取SKU快照列表.
     *
     * @param array $skuIds SKU ID列表
     * @return array SKU快照数据数组
     */
    public function getSkuSnapshots(array $skuIds): array
    {
        $skuIds = array_values(array_filter(array_unique(array_map('intval', $skuIds))));
        if ($skuIds === []) {
            return [];
        }

        $keys = array_map(fn (int $id) => $this->skuKey($id), $skuIds);
        $rawValues = $this->cache->mGet($keys);

        $snapshots = [];
        $missing = [];

        foreach ($skuIds as $index => $skuId) {
            $raw = $rawValues[$index] ?? null;
            if (! \is_string($raw) || $raw === '') {
                $missing[] = $skuId;
                continue;
            }

            $decoded = $this->decodeSnapshot($raw);
            if ($decoded === null) {
                $missing[] = $skuId;
                continue;
            }

            $snapshots[$skuId] = $decoded;
        }

        // 处理缺失的SKU数据
        if ($missing !== []) {
            $refreshed = $this->rememberSkus($missing);
            foreach ($refreshed as $id => $payload) {
                $snapshots[$id] = $payload;
            }
        }

        return $snapshots;
    }

    /**
     * 缓存商品数据.
     *
     * @param Product $product 商品模型实例
     * @param array $with 关联查询字段
     * @return array 缓存的商品数据
     */
    public function rememberProduct(Product $product, array $with = []): array
    {
        $relations = $this->normalizeRelations($with);
        $product->loadMissing($relations);

        $payload = $product->toArray();
        $productId = (int) ($product->id ?? 0);
        if ($productId > 0) {
            $this->persistProduct($productId, $payload);
        }

        foreach ($product->skus as $sku) {
            if ($sku instanceof ProductSku) {
                $this->rememberSku($sku, $product);
            }
        }

        return $payload;
    }

    /**
     * 缓存SKU数据.
     *
     * @param ProductSku $sku SKU模型实例
     * @param null|Product $product 商品模型实例
     * @return array 缓存的SKU数据
     */
    public function rememberSku(ProductSku $sku, ?Product $product = null): array
    {
        $product ??= $sku->product;
        if (! $product instanceof Product) {
            $product = $this->productModel->newQuery()->find($sku->product_id);
            if (! $product instanceof Product) {
                return [];
            }
        }

        $payload = $this->buildSkuPayload($product, $sku);
        $this->persistSku($sku->id, $payload);

        return $payload;
    }

    /**
     * 清除指定商品的缓存.
     *
     * @param int $productId 商品ID
     */
    public function evictProduct(int $productId): void
    {
        if ($productId <= 0) {
            return;
        }

        $this->cache->delete($this->productKey($productId));
    }

    /**
     * 删除指定SKU的缓存.
     *
     * @param array $skuIds SKU ID列表
     */
    public function deleteSkus(array $skuIds): void
    {
        $keys = array_map(
            fn (int $id) => $this->skuKey($id),
            array_filter(array_map('intval', $skuIds))
        );

        if ($keys === []) {
            return;
        }

        $this->cache->delete(...$keys);
    }

    /**
     * 根据商品ID从数据库获取并缓存商品数据.
     *
     * @param int $productId 商品ID
     * @param array $with 关联查询字段
     * @return null|array 商品数据或null
     */
    private function rememberProductById(int $productId, array $with): ?array
    {
        $query = $this->productModel->newQuery()->whereKey($productId);
        if ($with !== []) {
            $query->with($with);
        }

        /** @var null|Product $product */
        $product = $query->first();
        if (! $product instanceof Product) {
            $this->evictProduct($productId);
            return null;
        }

        return $this->rememberProduct($product, $with);
    }

    /**
     * 批量缓存SKU数据.
     *
     * @param array<int, int> $skuIds SKU ID列表
     * @return array<int, array<string, mixed>> SKU数据数组
     */
    private function rememberSkus(array $skuIds): array
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

            $results[$sku->id] = $this->rememberSku($sku, $sku->product);
        }

        return $results;
    }

    /**
     * 从缓存中获取商品数据.
     *
     * @param int $productId 商品ID
     * @return null|array 商品数据或null
     */
    private function fetchProductFromCache(int $productId): ?array
    {
        $raw = $this->cache->get($this->productKey($productId));
        if (! \is_string($raw) || $raw === '') {
            return null;
        }

        return $this->decodeSnapshot($raw);
    }

    /**
     * 检查缓存数据是否包含指定的关系数据.
     *
     * @param array $payload 缓存的数据
     * @param array $relations 需要的关系字段
     * @return bool 是否包含所有关系数据
     */
    private function containsRelations(array $payload, array $relations): bool
    {
        foreach ($relations as $relation) {
            if (! \array_key_exists($relation, $payload)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 持久化商品数据到缓存.
     *
     * @param int $productId 商品ID
     * @param array $payload 商品数据
     */
    private function persistProduct(int $productId, array $payload): void
    {
        $encoded = Json::encode($payload, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR);
        $this->cache->set($this->productKey($productId), $encoded);
    }

    /**
     * 持久化SKU数据到缓存.
     *
     * @param int $skuId SKU ID
     * @param array $payload SKU数据
     */
    private function persistSku(int $skuId, array $payload): void
    {
        $encoded = Json::encode($payload, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR);
        $this->cache->set($this->skuKey($skuId), $encoded);
    }

    /**
     * 构建SKU快照数据.
     *
     * @param Product $product 商品模型
     * @param ProductSku $sku SKU模型
     * @return array<string, mixed> SKU快照数据
     */
    private function buildSkuPayload(Product $product, ProductSku $sku): array
    {
        return [
            'product_id' => (int) $product->id,
            'product_code' => (string) $product->product_code,
            'product_name' => (string) $product->name,
            'product_status' => (string) $product->status,
            'product_image' => $product->main_image,
            'product_min_price' => (int) $product->min_price,
            'product_max_price' => (int) $product->max_price,
            'sku_id' => (int) $sku->id,
            'sku_code' => (string) $sku->sku_code,
            'sku_name' => (string) $sku->sku_name,
            'sku_status' => (string) $sku->status,
            'sku_image' => $sku->image,
            'spec_values' => $sku->spec_values ?? [],
            'sale_price' => (int) $sku->sale_price,
            'market_price' => (int) $sku->market_price,
            'cost_price' => (int) $sku->cost_price,
            'weight' => (float) $sku->weight,
            'warning_stock' => (int) $sku->warning_stock,
        ];
    }

    /**
     * 规范化关联关系数组.
     *
     * @param array $with 原始关联关系数组
     * @return array 规范化后的关联关系数组
     */
    private function normalizeRelations(array $with): array
    {
        $relations = $with === [] ? self::DEFAULT_WITH : $with;
        return array_values(array_unique(array_merge($relations, self::DEFAULT_WITH)));
    }

    /**
     * 解码快照数据.
     *
     * @param string $raw 原始快照字符串
     * @return null|array 解码后的数组或null
     */
    private function decodeSnapshot(string $raw): ?array
    {
        try {
            $decoded = json_decode($raw, true, 512, \JSON_THROW_ON_ERROR);
            return \is_array($decoded) ? $decoded : null;
        } catch (\JsonException) {
            return null;
        }
    }

    /**
     * 生成商品缓存键.
     *
     * @param int $productId 商品ID
     * @return string 商品缓存键
     */
    private function productKey(int $productId): string
    {
        return \sprintf(self::PRODUCT_KEY, $productId);
    }

    /**
     * 生成SKU缓存键.
     *
     * @param int $skuId SKU ID
     * @return string SKU缓存键
     */
    private function skuKey(int $skuId): string
    {
        return \sprintf(self::SKU_KEY, $skuId);
    }
}
