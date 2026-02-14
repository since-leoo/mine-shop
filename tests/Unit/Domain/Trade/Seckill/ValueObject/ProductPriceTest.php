<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\Seckill\ValueObject;

use App\Domain\Trade\Seckill\ValueObject\ProductPrice;
use PHPUnit\Framework\TestCase;

class ProductPriceTest extends TestCase
{
    public function testCreateValidPrice(): void
    {
        $price = new ProductPrice(10000, 8000);
        $this->assertSame(10000, $price->getOriginalPrice());
        $this->assertSame(8000, $price->getSeckillPrice());
    }

    public function testSeckillPriceCannotExceedOriginal(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ProductPrice(8000, 10000);
    }

    public function testEqualPriceIsAllowed(): void
    {
        $price = new ProductPrice(5000, 5000);
        $this->assertSame(5000, $price->getSeckillPrice());
    }

    public function testGetDiscount(): void
    {
        $price = new ProductPrice(10000, 8000);
        $this->assertSame(20.0, $price->getDiscount());
    }

    public function testGetDiscountZeroOriginal(): void
    {
        $price = new ProductPrice(0, 0);
        $this->assertSame(0.0, $price->getDiscount());
    }

    public function testGetSavings(): void
    {
        $price = new ProductPrice(10000, 7500);
        $this->assertSame(2500, $price->getSavings());
    }

    public function testToArray(): void
    {
        $price = new ProductPrice(10000, 8000);
        $this->assertSame([
            'original_price' => 10000,
            'seckill_price' => 8000,
        ], $price->toArray());
    }
}
