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

namespace App\Domain\Member\Api\Query;

use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use App\Infrastructure\Abstract\ICache;

/**
 * 面向 API 场景的购物车查询领域服务.
 *
 * 购物车基于缓存存储，不使用 IService 基类.
 */
final class DomainApiMemberCartQueryService
{
    private const CACHE_PREFIX = 'member:cart';

    private ICache $cache;

    public function __construct(
        ICache $cache,
        private readonly ProductSnapshotInterface $productCache,
    ) {
        $this->cache = clone $cache;
        $this->cache->setPrefix(self::CACHE_PREFIX);
    }

    /**
     * 返回购物车详细列表（含 sku/product 快照）.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listDetailed(int $memberId): array
    {
        $items = $this->fetchItems($memberId);
        if ($items === []) {
            return [];
        }

        $products = $this->batchFetchProducts($items);

        $result = [];
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $product = $products[$productId] ?? null;
            $sku = $product !== null
                ? $this->resolveSkuSnapshot($product, (int) ($item['sku_id'] ?? 0))
                : null;

            $result[] = $item + [
                'sku' => $sku,
                'product' => $product,
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchItems(int $memberId): array
    {
        $raw = $this->cache->hGetAll($this->cacheKey($memberId));
        if ($raw === []) {
            return [];
        }

        $items = [];
        foreach ($raw as $field => $value) {
            $decoded = json_decode((string) $value, true);
            if (! \is_array($decoded)) {
                continue;
            }
            $decoded['sku_id'] = (int) ($decoded['sku_id'] ?? (int) $field);
            $decoded['product_id'] = (int) ($decoded['product_id'] ?? 0);
            $decoded['quantity'] = (int) ($decoded['quantity'] ?? 0);
            $items[$decoded['sku_id']] = $decoded;
        }

        return $items;
    }

    private function cacheKey(int $memberId): string
    {
        return (string) $memberId;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function batchFetchProducts(array $items): array
    {
        $productIds = array_values(array_filter(array_unique(array_map(
            static fn (array $item) => (int) ($item['product_id'] ?? 0),
            $items
        ))));

        if ($productIds === []) {
            return [];
        }

        $snapshots = [];
        foreach ($productIds as $productId) {
            $snapshot = $this->productCache->getProduct($productId, ['skus']);
            if ($snapshot !== null) {
                $snapshots[$productId] = $snapshot;
            }
        }

        return $snapshots;
    }

    /**
     * @param array<string, mixed> $product
     * @return null|array<string, mixed>
     */
    private function resolveSkuSnapshot(array $product, int $skuId): ?array
    {
        $skus = $product['skus'] ?? [];
        if (! \is_array($skus)) {
            return null;
        }

        foreach ($skus as $sku) {
            if ((int) ($sku['id'] ?? 0) === $skuId) {
                return $sku;
            }
        }

        return null;
    }
}
