<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\Seckill\ValueObject;

use App\Domain\Trade\Seckill\ValueObject\ProductStock;
use PHPUnit\Framework\TestCase;

class ProductStockTest extends TestCase
{
    public function testCreateValidStock(): void
    {
        $stock = new ProductStock(100, 30);
        $this->assertSame(100, $stock->getQuantity());
        $this->assertSame(30, $stock->getSoldQuantity());
        $this->assertSame(70, $stock->getAvailableQuantity());
    }

    public function testSoldCannotExceedQuantity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ProductStock(50, 60);
    }

    public function testIsSoldOut(): void
    {
        $stock = new ProductStock(10, 10);
        $this->assertTrue($stock->isSoldOut());

        $stock2 = new ProductStock(10, 5);
        $this->assertFalse($stock2->isSoldOut());
    }

    public function testStockPercentage(): void
    {
        $stock = new ProductStock(100, 80);
        $this->assertSame(20.0, $stock->getStockPercentage());
    }

    public function testStockPercentageZeroQuantity(): void
    {
        $stock = new ProductStock(0, 0);
        $this->assertSame(0.0, $stock->getStockPercentage());
    }

    public function testIsLowStock(): void
    {
        $stock = new ProductStock(100, 85);
        $this->assertTrue($stock->isLowStock(20));

        $stock2 = new ProductStock(100, 50);
        $this->assertFalse($stock2->isLowStock(20));
    }

    public function testCanSell(): void
    {
        $stock = new ProductStock(100, 90);
        $this->assertTrue($stock->canSell(10));
        $this->assertFalse($stock->canSell(11));
    }

    public function testSellReturnsNewInstance(): void
    {
        $stock = new ProductStock(100, 50);
        $newStock = $stock->sell(10);
        $this->assertSame(60, $newStock->getSoldQuantity());
        $this->assertSame(50, $stock->getSoldQuantity()); // immutable
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
        $this->assertSame([
            'quantity' => 100,
            'sold_quantity' => 30,
            'available_quantity' => 70,
        ], $stock->toArray());
    }
}
