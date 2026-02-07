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

final class PriceRange
{
    public function __construct(private float $min, private float $max)
    {
        if ($min > $max) {
            throw new \InvalidArgumentException('最低价不能高于最高价');
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
