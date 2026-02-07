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

namespace App\Domain\GroupBuy\ValueObject;

/**
 * 价格值对象.
 */
final class PriceVo
{
    public function __construct(
        private readonly float $originalPrice,
        private readonly float $groupPrice
    ) {
        $this->validate();
    }

    /**
     * 获取原价.
     */
    public function getOriginalPrice(): float
    {
        return $this->originalPrice;
    }

    /**
     * 获取团购价.
     */
    public function getGroupPrice(): float
    {
        return $this->groupPrice;
    }

    /**
     * 计算折扣率.
     */
    public function getDiscountRate(): float
    {
        return round(($this->groupPrice / $this->originalPrice) * 100, 2);
    }

    /**
     * 计算优惠金额.
     */
    public function getDiscountAmount(): float
    {
        return round($this->originalPrice - $this->groupPrice, 2);
    }

    /**
     * 验证价格业务规则.
     */
    private function validate(): void
    {
        // 业务规则：团购价必须小于原价
        if ($this->groupPrice >= $this->originalPrice) {
            throw new \DomainException('团购价必须小于原价');
        }
    }
}
