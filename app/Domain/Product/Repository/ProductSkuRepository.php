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

namespace App\Domain\Product\Repository;

use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Product\ProductSku;
use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Builder;

/**
 * @extends IRepository<ProductSku>
 */
final class ProductSkuRepository extends IRepository
{
    public function __construct(protected readonly ProductSku $model) {}

    /**
     * @param int[] $ids
     */
    public function findManyWithProduct(array $ids): Collection
    {
        if ($ids === []) {
            return Collection::make();
        }

        return $this->getQuery()
            ->with('product')
            ->whereIn('id', $ids)
            ->get();
    }

    public function findSaleableWithProduct(int $skuId): ?ProductSku
    {
        /** @var null|ProductSku $sku */
        $sku = $this->getQuery()
            ->with('product')
            ->whereKey($skuId)
            ->first();

        return $sku;
    }

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query;
    }
}
