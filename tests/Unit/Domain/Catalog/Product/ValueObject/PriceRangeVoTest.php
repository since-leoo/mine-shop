<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Catalog\Product\ValueObject;

use App\Domain\Catalog\Product\Entity\ProductSkuEntity;
use App\Domain\Catalog\Product\ValueObject\PriceRangeVo;
use PHPUnit\Framework\TestCase;

class PriceRangeVoTest extends TestCase
{
    public function testValid(): void
    {
        $vo = new PriceRangeVo(1000, 5000);
        $this->assertSame(1000, $vo->minPrice);
        $this->assertSame(5000, $vo->maxPrice);
    }

    public function testEqualPrices(): void
    {
        $vo = new PriceRangeVo(3000, 3000);
        $this->assertSame(3000, $vo->minPrice);
    }

    public function testMinGreaterThanMaxThrows(): void
    {
        $this->expectException(\DomainException::class);
        new PriceRangeVo(5000, 1000);
    }

    public function testNegativeMinThrows(): void
    {
        $this->expectException(\DomainException::class);
        new PriceRangeVo(-1, 1000);
    }

    public function testFromSkus(): void
    {
        $sku1 = new ProductSkuEntity();
        $sku1->setSalePrice(1000);
        $sku2 = new ProductSkuEntity();
        $sku2->setSalePrice(5000);
        $sku3 = new ProductSkuEntity();
        $sku3->setSalePrice(3000);

        $vo = PriceRangeVo::fromSkus([$sku1, $sku2, $sku3]);
        $this->assertSame(1000, $vo->minPrice);
        $this->assertSame(5000, $vo->maxPrice);
    }

    public function testFromSkusEmpty(): void
    {
        $vo = PriceRangeVo::fromSkus([]);
        $this->assertSame(0, $vo->minPrice);
        $this->assertSame(0, $vo->maxPrice);
    }

    public function testEquals(): void
    {
        $vo1 = new PriceRangeVo(1000, 5000);
        $vo2 = new PriceRangeVo(1000, 5000);
        $vo3 = new PriceRangeVo(1000, 6000);
        $this->assertTrue($vo1->equals($vo2));
        $this->assertFalse($vo1->equals($vo3));
    }
}
