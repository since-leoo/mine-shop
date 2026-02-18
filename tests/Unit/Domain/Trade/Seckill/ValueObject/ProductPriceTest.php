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

namespace HyperfTests\Unit\Domain\Trade\Seckill\ValueObject;

use App\Domain\Trade\Seckill\ValueObject\ProductPrice;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class ProductPriceTest extends TestCase
{
    public function testCreateValidPrice(): void
    {
        $price = new ProductPrice(10000, 8000);
        self::assertSame(10000, $price->getOriginalPrice());
        self::assertSame(8000, $price->getSeckillPrice());
    }

    public function testSeckillPriceCannotExceedOriginal(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ProductPrice(8000, 10000);
    }

    public function testEqualPriceIsAllowed(): void
    {
        $price = new ProductPrice(5000, 5000);
        self::assertSame(5000, $price->getSeckillPrice());
    }

    public function testGetDiscount(): void
    {
        $price = new ProductPrice(10000, 8000);
        self::assertSame(20.0, $price->getDiscount());
    }

    public function testGetDiscountZeroOriginal(): void
    {
        $price = new ProductPrice(0, 0);
        self::assertSame(0.0, $price->getDiscount());
    }

    public function testGetSavings(): void
    {
        $price = new ProductPrice(10000, 7500);
        self::assertSame(2500, $price->getSavings());
    }

    public function testToArray(): void
    {
        $price = new ProductPrice(10000, 8000);
        self::assertSame([
            'original_price' => 10000,
            'seckill_price' => 8000,
        ], $price->toArray());
    }
}
