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

namespace App\Domain\Catalog\Product\ValueObject;

use App\Domain\Catalog\Product\Entity\ProductSkuEntity;

use App\Infrastructure\Error\CatalogError;

/**
 * 价格范围值对象.
 */
final class PriceRangeVo
{
    public function __construct(
        public readonly int $minPrice,
        public readonly int $maxPrice
    ) {
        if ($minPrice < 0) {
            throw CatalogError::MIN_PRICE_NEGATIVE->exception();
        }

        if ($maxPrice < 0) {
            throw CatalogError::MAX_PRICE_NEGATIVE->exception();
        }

        if ($minPrice > $maxPrice) {
            throw CatalogError::PRICE_RANGE_INVALID->exception();
        }
    }

    /**
     * 从 SKU 列表计算价格范围.
     *
     * @param array<int, ProductSkuEntity> $skus
     */
    public static function fromSkus(array $skus): self
    {
        if ($skus === []) {
            return new self(0, 0);
        }

        $prices = array_filter(
            array_map(static fn ($sku) => $sku->getSalePrice(), $skus),
            static fn (int $price) => $price >= 0
        );

        if ($prices === []) {
            return new self(0, 0);
        }

        return new self(min($prices), max($prices));
    }

    public function equals(self $other): bool
    {
        return $this->minPrice === $other->minPrice
            && $this->maxPrice === $other->maxPrice;
    }
}
