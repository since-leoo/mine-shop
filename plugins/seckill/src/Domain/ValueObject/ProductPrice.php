<?php

declare(strict_types=1);

namespace Plugin\Since\Seckill\Domain\ValueObject;

final class ProductPrice
{
    private readonly int $originalPrice;

    private readonly int $seckillPrice;

    public function __construct(int $originalPrice, int $seckillPrice)
    {
        $this->originalPrice = $originalPrice;
        $this->seckillPrice = $seckillPrice;
        $this->validate();
    }

    public function getOriginalPrice(): int
    {
        return $this->originalPrice;
    }

    public function getSeckillPrice(): int
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

    public function getSavings(): int
    {
        return $this->originalPrice - $this->seckillPrice;
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
        if ($this->seckillPrice > $this->originalPrice) {
            throw new \InvalidArgumentException('秒杀价不能高于原价');
        }
    }
}
