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

namespace HyperfTests\Unit\Domain\Catalog\Product\ValueObject;

use App\Domain\Catalog\Product\Entity\ProductSkuEntity;
use App\Domain\Catalog\Product\ValueObject\PriceRangeVo;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class PriceRangeVoTest extends TestCase
{
    public function testValid(): void
    {
        $vo = new PriceRangeVo(1000, 5000);
        self::assertSame(1000, $vo->minPrice);
        self::assertSame(5000, $vo->maxPrice);
    }

    public function testEqualPrices(): void
    {
        $vo = new PriceRangeVo(3000, 3000);
        self::assertSame(3000, $vo->minPrice);
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
        self::assertSame(1000, $vo->minPrice);
        self::assertSame(5000, $vo->maxPrice);
    }

    public function testFromSkusEmpty(): void
    {
        $vo = PriceRangeVo::fromSkus([]);
        self::assertSame(0, $vo->minPrice);
        self::assertSame(0, $vo->maxPrice);
    }

    public function testEquals(): void
    {
        $vo1 = new PriceRangeVo(1000, 5000);
        $vo2 = new PriceRangeVo(1000, 5000);
        $vo3 = new PriceRangeVo(1000, 6000);
        self::assertTrue($vo1->equals($vo2));
        self::assertFalse($vo1->equals($vo3));
    }
}
