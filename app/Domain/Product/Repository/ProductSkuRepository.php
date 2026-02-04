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

/**
 * @extends IRepository<ProductSku>
 */
final class ProductSkuRepository extends IRepository
{
    public function __construct(protected readonly ProductSku $model) {}

    public function findSaleableWithProduct(int $skuId): ?ProductSku
    {
        /** @var null|ProductSku $sku */
        $sku = $this->getQuery()->with('product')->whereKey($skuId)->first();

        return $sku ?: null;
    }
}
