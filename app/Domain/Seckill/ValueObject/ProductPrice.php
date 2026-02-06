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

namespace App\Domain\Seckill\ValueObject;

/**
 * 商品价格值对象.
 */
final class ProductPrice
{
    private readonly float $originalPrice;

    private readonly float $seckillPrice;

    public function __construct(float $originalPrice, float $seckillPrice)
    {
        $this->originalPrice = $originalPrice;
        $this->seckillPrice = $seckillPrice;

        $this->validate();
    }

    public function getOriginalPrice(): float
    {
        return $this->originalPrice;
    }

    public function getSeckillPrice(): float
    {
        return $this->seckillPrice;
    }

    public function getDiscount(): float
    {
        if ($this->originalPrice === 0) {
            return 0;
        }

        return round(($this->originalPrice - $this->seckillPrice) / $this->originalPrice * 100, 2);
    }

    public function getSavings(): float
    {
        return round($this->originalPrice - $this->seckillPrice, 2);
    }

    public function toArray(): array
    {
        return [
            'original_price' => $this->originalPrice,
            'seckill_price' => $this->seckillPrice,
        ];
    }

    private function validate(): void
    {
        // 业务规则：秒杀价不能高于原价
        if ($this->seckillPrice > $this->originalPrice) {
            throw new \InvalidArgumentException('秒杀价不能高于原价');
        }
    }
}
