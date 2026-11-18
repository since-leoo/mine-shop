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

use App\Infrastructure\Error\CatalogError;

final class PriceRange
{
    public function __construct(private float $min, private float $max)
    {
        if ($min > $max) {
            throw CatalogError::PRICE_RANGE_INVALID->invalid();
        }
    }

    public function min(): float
    {
        return $this->min;
    }

    public function max(): float
    {
        return $this->max;
    }
}
