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

namespace App\Domain\Catalog\Product\Event;

/**
 * 商品创建事件.
 */
final class ProductCreated
{
    /**
     * @param int $productId 商品ID
     * @param array<int, int> $skuIds SKU ID列表
     * @param array<int, array{sku_id: int, stock: int}> $stockData 库存数据
     */
    public function __construct(
        public readonly int $productId,
        public readonly array $skuIds = [],
        public readonly array $stockData = []
    ) {}
}
