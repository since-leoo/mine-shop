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

use App\Domain\Trade\Seckill\ValueObject\ProductStock;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class ProductStockTest extends TestCase
{
    public function testCreateValidStock(): void
    {
        $stock = new ProductStock(100, 30);
        self::assertSame(100, $stock->getQuantity());
        self::assertSame(30, $stock->getSoldQuantity());
        self::assertSame(70, $stock->getAvailableQuantity());
    }

    public function testSoldCannotExceedQuantity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ProductStock(50, 60);
    }

    public function testIsSoldOut(): void
    {
        $stock = new ProductStock(10, 10);
        self::assertTrue($stock->isSoldOut());

        $stock2 = new ProductStock(10, 5);
        self::assertFalse($stock2->isSoldOut());
    }

    public function testStockPercentage(): void
    {
        $stock = new ProductStock(100, 80);
        self::assertSame(20.0, $stock->getStockPercentage());
    }

    public function testStockPercentageZeroQuantity(): void
    {
        $stock = new ProductStock(0, 0);
        self::assertSame(0.0, $stock->getStockPercentage());
    }

    public function testIsLowStock(): void
    {
        $stock = new ProductStock(100, 85);
        self::assertTrue($stock->isLowStock(20));

        $stock2 = new ProductStock(100, 50);
        self::assertFalse($stock2->isLowStock(20));
    }

    public function testCanSell(): void
    {
        $stock = new ProductStock(100, 90);
        self::assertTrue($stock->canSell(10));
        self::assertFalse($stock->canSell(11));
    }

    public function testSellReturnsNewInstance(): void
    {
        $stock = new ProductStock(100, 50);
        $newStock = $stock->sell(10);
        self::assertSame(60, $newStock->getSoldQuantity());
        self::assertSame(50, $stock->getSoldQuantity()); // immutable
    }

    public function testSellThrowsWhenInsufficientStock(): void
    {
        $stock = new ProductStock(100, 95);
        $this->expectException(\DomainException::class);
        $stock->sell(10);
    }

    public function testToArray(): void
    {
        $stock = new ProductStock(100, 30);
        self::assertSame([
            'quantity' => 100,
            'sold_quantity' => 30,
            'available_quantity' => 70,
        ], $stock->toArray());
    }
}
