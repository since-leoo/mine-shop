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
 * 商品变更值对象.
 */
final class ProductChangeVo
{
    /**
     * @param int $productId 商品ID
     * @param array<int, int> $deletedSkuIds 删除的SKU ID列表
     * @param array<int, int> $deletedAttributeIds 删除的属性ID列表
     * @param bool $priceChanged 价格是否变更
     * @param bool $statusChanged 状态是否变更
     * @param bool $stockChanged 库存是否变更
     * @param bool $freightChanged 运费配置是否变更
     */
    public function __construct(
        public readonly int $productId,
        public readonly array $deletedSkuIds = [],
        public readonly array $deletedAttributeIds = [],
        public readonly bool $priceChanged = false,
        public readonly bool $statusChanged = false,
        public readonly bool $stockChanged = false,
        public readonly bool $freightChanged = false
    ) {}

    public function hasSkuDeleted(): bool
    {
        return $this->deletedSkuIds !== [];
    }

    public function hasAttributeDeleted(): bool
    {
        return $this->deletedAttributeIds !== [];
    }

    public function needsCacheRefresh(): bool
    {
        return $this->priceChanged || $this->statusChanged || $this->stockChanged || $this->freightChanged;
    }
}
