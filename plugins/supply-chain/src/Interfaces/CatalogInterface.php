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

namespace Plugin\SupplyChain\Interfaces;

use Plugin\SupplyChain\Interfaces\Data\ProductQuery;

interface CatalogInterface
{
    /** @return array{items: array, total: int, page: int, page_size: int} */
    public function search(ProductQuery $query): array;

    /** @return list<array> Normalized product records; monetary fields are integer cents. */
    public function details(array $externalProductIds): array;

    /** @return list<array{external_product_id: string, external_sku_id: string, stock: int}> */
    public function stocks(array $externalProductIds): array;

    public function suppliers(): array;

    public function categories(): array;

    public function supplierCategories(): array;
}
