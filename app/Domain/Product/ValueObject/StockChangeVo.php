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

namespace App\Domain\Product\ValueObject;

/**
 * 库存变更值对象.
 */
final class StockChangeVo
{
    /**
     * @param int $skuId SKU ID
     * @param int $oldStock 原库存
     * @param int $newStock 新库存
     * @param int $delta 变更量
     * @param bool $isLowStock 是否低库存
     */
    public function __construct(
        public readonly int $skuId,
        public readonly int $oldStock,
        public readonly int $newStock,
        public readonly int $delta,
        public readonly bool $isLowStock
    ) {}

    public function hasChanged(): bool
    {
        return $this->delta !== 0;
    }

    public function isIncrease(): bool
    {
        return $this->delta > 0;
    }

    public function isDecrease(): bool
    {
        return $this->delta < 0;
    }
}
