<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Catalog\Product\Entity;

use App\Domain\Catalog\Product\Entity\ProductSkuEntity;
use PHPUnit\Framework\TestCase;

class ProductSkuEntityTest extends TestCase
{
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

    public function testBasicProperties(): void
    {
        $sku = $this->makeSku();
        $this->assertSame(1, $sku->getId());
        $this->assertSame('SKU-001', $sku->getSkuCode());
        $this->assertSame('红色 L', $sku->getSkuName());
        $this->assertSame(9900, $sku->getSalePrice());
        $this->assertSame(100, $sku->getStock());
    }

    public function testIsActive(): void
    {
        $sku = $this->makeSku();
        $this->assertTrue($sku->isActive());
        $sku->markInactive();
        $this->assertFalse($sku->isActive());
        $sku->markActive();
        $this->assertTrue($sku->isActive());
    }

    public function testIsLowStock(): void
    {
        $sku = $this->makeSku(); // stock=100, warningStock=10
        $this->assertFalse($sku->isLowStock());
        $sku->setStock(10);
        $this->assertTrue($sku->isLowStock());
        $sku->setStock(5);
        $this->assertTrue($sku->isLowStock());
    }

    public function testIsLowStockNoWarning(): void
    {
        $sku = $this->makeSku();
        $sku->setWarningStock(0);
        $sku->setStock(1);
        $this->assertFalse($sku->isLowStock());
    }

    public function testIncreaseStock(): void
    {
        $sku = $this->makeSku();
        $sku->increaseStock(50);
        $this->assertSame(150, $sku->getStock());
    }

    public function testDecreaseStock(): void
    {
        $sku = $this->makeSku();
        $sku->decreaseStock(30);
        $this->assertSame(70, $sku->getStock());
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
        $this->assertTrue(true);
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
        $this->assertSame(1, $arr['id']);
        $this->assertSame('SKU-001', $arr['sku_code']);
        $this->assertSame(9900, $arr['sale_price']);
        $this->assertSame(100, $arr['stock']);
    }
}
