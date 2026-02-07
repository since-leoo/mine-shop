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
 * 商品删除事件.
 */
final class ProductDeleted
{
    /**
     * @param int $productId 商品ID
     * @param array<int, int> $skuIds SKU ID列表
     */
    public function __construct(
        public readonly int $productId,
        public readonly array $skuIds = []
    ) {}
}
