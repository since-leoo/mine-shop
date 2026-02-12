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

namespace App\Domain\Trade\GroupBuy\ValueObject;

final class PriceVo
{
    public function __construct(
        private readonly int $originalPrice,
        private readonly int $groupPrice
    ) {
        $this->validate();
    }

    public function getOriginalPrice(): int
    {
        return $this->originalPrice;
    }

    public function getGroupPrice(): int
    {
        return $this->groupPrice;
    }

    public function getDiscountRate(): float
    {
        return round(($this->groupPrice / $this->originalPrice) * 100, 2);
    }

    public function getDiscountAmount(): int
    {
        return $this->originalPrice - $this->groupPrice;
    }

    private function validate(): void
    {
        if ($this->groupPrice >= $this->originalPrice) {
            throw new \DomainException('团购价必须小于原价');
        }
    }
}
