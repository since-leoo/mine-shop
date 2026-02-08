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

namespace App\Domain\Marketing\Seckill\Repository;

use App\Domain\Marketing\Seckill\Entity\SeckillProductEntity;
use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Product\Product;
use App\Infrastructure\Model\Product\ProductSku;
use App\Infrastructure\Model\Seckill\SeckillProduct;
use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Builder;

/**
 * 秒杀商品仓储.
 *
 * @extends IRepository<SeckillProduct>
 */
final class SeckillProductRepository extends IRepository
{
    public function __construct(protected readonly SeckillProduct $model) {}

    public function createFromEntity(SeckillProductEntity $entity): SeckillProduct
    {
        $product = SeckillProduct::create($entity->toArray());
        $entity->setId((int) $product->id);
        return $product;
    }

    public function updateFromEntity(SeckillProductEntity $entity): bool
    {
        $product = SeckillProduct::find($entity->getId());
        return $product && $product->update($entity->toArray());
    }

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(isset($params['session_id']), static fn (Builder $q) => $q->where('session_id', $params['session_id']))
            ->when(isset($params['activity_id']), static fn (Builder $q) => $q->where('activity_id', $params['activity_id']))
            ->when(isset($params['product_id']), static fn (Builder $q) => $q->where('product_id', $params['product_id']))
            ->when(isset($params['is_enabled']), static fn (Builder $q) => $q->where('is_enabled', (bool) $params['is_enabled']))
            ->orderBy('sort_order')
            ->orderBy('id', 'desc');
    }

    public function handleItems(Collection $items): Collection
    {
        $productIds = $items->pluck('product_id')->unique()->toArray();
        $skuIds = $items->pluck('product_sku_id')->unique()->toArray();

        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
        $skus = ProductSku::whereIn('id', $skuIds)->get()->keyBy('id');

        return $items->map(static function ($item) use ($products, $skus) {
            $product = $products->get($item->product_id);
            $sku = $skus->get($item->product_sku_id);

            $item->product_name = $product?->name ?? '';
            $item->product_image = $product?->main_image ?? '';
            $item->sku_name = $sku?->sku_name ?? '';
            $item->sku_code = $sku?->sku_code ?? '';
            $item->sku_stock = $sku?->stock ?? 0;

            return $item;
        });
    }

    /**
     * 获取指定场次的商品列表.
     *
     * @return SeckillProduct[]
     */
    public function findBySessionId(int $sessionId): array
    {
        return SeckillProduct::where('session_id', $sessionId)
            ->orderBy('sort_order')
            ->get()
            ->all();
    }

    /**
     * 统计场次下的商品数.
     */
    public function countBySessionId(int $sessionId): int
    {
        return SeckillProduct::where('session_id', $sessionId)->count();
    }

    /**
     * 检查商品是否已在场次中.
     */
    public function existsInSession(int $sessionId, int $productSkuId): bool
    {
        return SeckillProduct::where('session_id', $sessionId)
            ->where('product_sku_id', $productSkuId)
            ->exists();
    }

    /**
     * 批量创建商品.
     *
     * @param SeckillProductEntity[] $entities
     * @return SeckillProduct[]
     */
    public function batchCreate(array $entities): array
    {
        $products = [];
        foreach ($entities as $entity) {
            $products[] = $this->createFromEntity($entity);
        }
        return $products;
    }

    /**
     * 获取指定场次下已启用的商品列表（带商品主表关联）.
     *
     * @return SeckillProduct[]
     */
    public function findEnabledBySessionIdWithProduct(int $sessionId, int $limit = 6): array
    {
        return SeckillProduct::where('session_id', $sessionId)
            ->where('is_enabled', true)
            ->with('product:id,name,main_image')
            ->orderBy('sort_order')
            ->limit($limit)
            ->get()
            ->all();
    }

    /**
     * 增加秒杀商品已售数量.
     */
    public function incrementSoldQuantity(int $skuId, int $sessionId, int $quantity): void
    {
        SeckillProduct::where('session_id', $sessionId)
            ->where('product_sku_id', $skuId)
            ->increment('sold_quantity', $quantity);
    }
}
