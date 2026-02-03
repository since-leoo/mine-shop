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

use App\Domain\Product\Repository\ProductSkuRepository;
use App\Infrastructure\Abstract\ICache;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Model\Product\Product;
use App\Infrastructure\Model\Product\ProductSku;
use App\Interface\Common\ResultCode;
use Carbon\Carbon;
final class MemberCartService
{
    private const MAX_QUANTITY = 999;

    private const CACHE_PREFIX = 'member:cart';

    private ICache $cache;

    public function __construct(
        ICache $cache,
        private readonly ProductSkuRepository $skuRepository
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
        if ($items === []) {return [];}

        $skuIds = array_column($items, 'sku_id');
        $skus = $this->skuRepository->findManyWithProduct($skuIds)->keyBy('id');

        $result = [];
        foreach ($items as $item) {
            /** @var null|ProductSku $sku */
            $sku = $skus->get($item['sku_id']);
            $result[] = $item + [
                'sku' => $sku,
                'product' => $sku?->product,
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
            if ($sku === null || $product === null || ! $this->isSaleable($product, $sku)) {
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
        if ($raw === []) {return [];}

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
        $this->cache->hSet($this->cacheKey($memberId), (string) $item['sku_id'],
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

    private function isSaleable(Product $product, ProductSku $sku): bool
    {
        if ($product->status !== Product::STATUS_ACTIVE) {
            return false;
        }

        return $sku->status === ProductSku::STATUS_ACTIVE;
    }
}
