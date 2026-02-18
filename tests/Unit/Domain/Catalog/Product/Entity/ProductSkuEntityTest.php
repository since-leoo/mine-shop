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

namespace HyperfTests\Unit\Domain\Catalog\Product\Entity;

use App\Domain\Catalog\Product\Entity\ProductSkuEntity;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class ProductSkuEntityTest extends TestCase
{
    public function testBasicProperties(): void
    {
        $sku = $this->makeSku();
        self::assertSame(1, $sku->getId());
        self::assertSame('SKU-001', $sku->getSkuCode());
        self::assertSame('红色 L', $sku->getSkuName());
        self::assertSame(9900, $sku->getSalePrice());
        self::assertSame(100, $sku->getStock());
    }

    public function testIsActive(): void
    {
        $sku = $this->makeSku();
        self::assertTrue($sku->isActive());
        $sku->markInactive();
        self::assertFalse($sku->isActive());
        $sku->markActive();
        self::assertTrue($sku->isActive());
    }

    public function testIsLowStock(): void
    {
        $sku = $this->makeSku(); // stock=100, warningStock=10
        self::assertFalse($sku->isLowStock());
        $sku->setStock(10);
        self::assertTrue($sku->isLowStock());
        $sku->setStock(5);
        self::assertTrue($sku->isLowStock());
    }

    public function testIsLowStockNoWarning(): void
    {
        $sku = $this->makeSku();
        $sku->setWarningStock(0);
        $sku->setStock(1);
        self::assertFalse($sku->isLowStock());
    }

    public function testIncreaseStock(): void
    {
        $sku = $this->makeSku();
        $sku->increaseStock(50);
        self::assertSame(150, $sku->getStock());
    }

    public function testDecreaseStock(): void
    {
        $sku = $this->makeSku();
        $sku->decreaseStock(30);
        self::assertSame(70, $sku->getStock());
    }

    public function testDecreaseStockBelowZeroThrows(): void
    {
        $sku = $this->makeSku();
        $this->expectException(\Throwable::class);
        $sku->decreaseStock(200);
    }

    public function testEnsureStockAvailable(): void
    {
        $sku = $this->makeSku();
        $sku->ensureStockAvailable(50); // should not throw
        self::assertTrue(true);
    }

    public function testEnsureStockAvailableInsufficientThrows(): void
    {
        $sku = $this->makeSku();
        $this->expectException(\Throwable::class);
        $sku->ensureStockAvailable(200);
    }

    public function testEnsureStockAvailableZeroThrows(): void
    {
        $sku = $this->makeSku();
        $this->expectException(\Throwable::class);
        $sku->ensureStockAvailable(0);
    }

    public function testToArray(): void
    {
        $sku = $this->makeSku();
        $arr = $sku->toArray();
        self::assertSame(1, $arr['id']);
        self::assertSame('SKU-001', $arr['sku_code']);
        self::assertSame(9900, $arr['sale_price']);
        self::assertSame(100, $arr['stock']);
    }

    private function makeSku(): ProductSkuEntity
    {
        $sku = new ProductSkuEntity();
        $sku->setId(1);
        $sku->setSkuCode('SKU-001');
        $sku->setSkuName('红色 L');
        $sku->setCostPrice(5000);
        $sku->setMarketPrice(12000);
        $sku->setSalePrice(9900);
        $sku->setStock(100);
        $sku->setWarningStock(10);
        $sku->setWeight(0.5);
        $sku->setStatus('active');
        return $sku;
    }
}
