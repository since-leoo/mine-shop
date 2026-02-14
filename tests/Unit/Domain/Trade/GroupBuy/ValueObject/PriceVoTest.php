<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\GroupBuy\ValueObject;

use App\Domain\Trade\GroupBuy\ValueObject\PriceVo;
use PHPUnit\Framework\TestCase;

class PriceVoTest extends TestCase
{
    public function testValidPrice(): void
    {
        $vo = new PriceVo(10000, 8000);
        $this->assertSame(10000, $vo->getOriginalPrice());
        $this->assertSame(8000, $vo->getGroupPrice());
    }

    public function testGroupPriceMustBeLessThanOriginal(): void
    {
        $this->expectException(\DomainException::class);
        new PriceVo(8000, 10000);
    }

    public function testEqualPriceThrows(): void
    {
        $this->expectException(\DomainException::class);
        new PriceVo(5000, 5000);
    }

    public function testGetDiscountRate(): void
    {
        $vo = new PriceVo(10000, 8000);
        $this->assertSame(80.0, $vo->getDiscountRate());
    }

    public function testGetDiscountAmount(): void
    {
        $vo = new PriceVo(10000, 8000);
        $this->assertSame(2000, $vo->getDiscountAmount());
    }
}
