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

use App\Domain\Catalog\Product\ValueObject\ProductChangeVo;

/**
 * 商品更新事件.
 */
final class ProductUpdated
{
    /**
     * @param int $productId 商品ID
     * @param ProductChangeVo $changes 变更信息
     * @param array<int, array{sku_id: int, stock: int}> $stockData 库存数据
     */
    public function __construct(
        public readonly int $productId,
        public readonly ProductChangeVo $changes,
        public readonly array $stockData = []
    ) {}
}
