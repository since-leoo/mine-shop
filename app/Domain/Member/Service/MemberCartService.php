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

namespace App\Domain\Member\Service;

use App\Domain\Product\Contract\ProductSnapshotInterface;
use App\Domain\Product\Repository\ProductSkuRepository;
use App\Infrastructure\Abstract\ICache;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Model\Product\Product;
use App\Infrastructure\Model\Product\ProductSku;
use App\Interface\Common\ResultCode;
use Carbon\Carbon;

final class MemberCartService extends IService
{
    private const MAX_QUANTITY = 999;

    private const CACHE_PREFIX = 'member:cart';

    private ICache $cache;

    public function __construct(
        ICache $cache,
        private readonly ProductSkuRepository $skuRepository,
        private readonly ProductSnapshotInterface $productCache
    ) {
        $this->cache = clone $cache;
        $this->cache->setPrefix(self::CACHE_PREFIX);
    }

    /**
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

    public function addItem(int $memberId, int $skuId, int $quantity, bool $selected = true): void
    {
        $sku = $this->ensureSaleableSku($skuId);
        $quantity = $this->normalizeQuantity($quantity);

        $items = $this->fetchItems($memberId);
        $cartItem = $items[$skuId] ?? [
            'sku_id' => $sku->id,
            'product_id' => $sku->product_id,
            'quantity' => 0,
            'is_selected' => true,
            'created_at' => Carbon::now()->toIso8601String(),
        ];

        $cartItem['quantity'] = $this->normalizeQuantity($cartItem['quantity'] + $quantity);
        $selected && $cartItem['is_selected'] = true;
        $cartItem['updated_at'] = Carbon::now()->toIso8601String();

        $this->persistItem($memberId, $cartItem);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function updateItem(int $memberId, int $skuId, array $payload): void
    {
        $items = $this->fetchItems($memberId);
        if (! isset($items[$skuId])) {
            throw new BusinessException(ResultCode::NOT_FOUND, '购物车条目不存在');
        }

        $cartItem = $items[$skuId];
        $cartItem['quantity'] = $this->normalizeQuantity((int) ($payload['quantity'] ?? 1));
        $cartItem['is_selected'] = (bool) ($payload['is_selected'] ?? false);
        $cartItem['updated_at'] = Carbon::now()->toIso8601String();

        $this->persistItem($memberId, $cartItem);
    }

    public function removeItem(int $memberId, int $skuId): void
    {
        $this->cache->hDel($this->cacheKey($memberId), (string) $skuId);
    }

    public function clearInvalid(int $memberId): void
    {
        $items = $this->listDetailed($memberId);
        foreach ($items as $item) {
            $sku = $item['sku'] ?? null;
            $product = $item['product'] ?? null;
            if (! $this->isSaleable($product, $sku)) {
                $this->removeItem($memberId, (int) $item['sku_id']);
            }
        }
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
            $decoded['is_selected'] = (bool) ($decoded['is_selected'] ?? true);
            $items[$decoded['sku_id']] = $decoded;
        }

        return $items;
    }

    private function persistItem(int $memberId, array $item): void
    {
        $this->cache->hSet(
            $this->cacheKey($memberId),
            (string) $item['sku_id'],
            json_encode($item, \JSON_UNESCAPED_UNICODE)
        );
    }

    private function cacheKey(int $memberId): string
    {
        return (string) $memberId;
    }

    private function normalizeQuantity(int $quantity): int
    {
        if ($quantity < 1) {
            $quantity = 1;
        }
        if ($quantity > self::MAX_QUANTITY) {
            $quantity = self::MAX_QUANTITY;
        }
        return $quantity;
    }

    private function ensureSaleableSku(int $skuId): ProductSku
    {
        $sku = $this->skuRepository->findSaleableWithProduct($skuId);
        if ($sku === null || $sku->product === null || ! $this->isSaleable($sku->product, $sku)) {
            throw new BusinessException(ResultCode::NOT_FOUND, '商品不存在或已下架');
        }
        return $sku;
    }

    /**
     * @param null|array<string, mixed>|Product $product
     * @param null|array<string, mixed>|ProductSku $sku
     */
    private function isSaleable(array|Product|null $product, array|ProductSku|null $sku): bool
    {
        if ($product === null || $sku === null) {
            return false;
        }

        $productStatus = $product instanceof Product
            ? $product->status
            : (string) ($product['status'] ?? '');

        if ($productStatus !== Product::STATUS_ACTIVE) {
            return false;
        }

        $skuStatus = $sku instanceof ProductSku
            ? $sku->status
            : (string) ($sku['status'] ?? '');

        return $skuStatus === ProductSku::STATUS_ACTIVE;
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
