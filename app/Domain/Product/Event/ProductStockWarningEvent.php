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

namespace App\Domain\Product\Event;

/**
 * 商品库存预警事件.
 */
final class ProductStockWarningEvent
{
    private int $skuId;

    private int $stock;

    private int $threshold;

    public function __construct(int $skuId, int $stock, int $threshold)
    {
        $this->skuId = $skuId;
        $this->stock = $stock;
        $this->threshold = $threshold;
    }

    public function getSkuId(): int
    {
        return $this->skuId;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function getThreshold(): int
    {
        return $this->threshold;
    }
}
