<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\Order\Entity;

use App\Domain\Trade\Order\Entity\OrderItemEntity;
use PHPUnit\Framework\TestCase;

class OrderItemEntityTest extends TestCase
{
    public function testFromPayload(): void
    {
        $item = OrderItemEntity::fromPayload([
            'sku_id' => 100,
            'product_id' => 50,
            'product_name' => '商品A',
            'sku_name' => 'SKU-A',
            'quantity' => 2,
            'unit_price' => 5000,
            'weight' => 1.5,
        ]);
        $this->assertSame(100, $item->getSkuId());
        $this->assertSame(50, $item->getProductId());
        $this->assertSame('商品A', $item->getProductName());
        $this->assertSame(2, $item->getQuantity());
        $this->assertSame(5000, $item->getUnitPrice());
        $this->assertSame(10000, $item->getTotalPrice());
        $this->assertSame(1.5, $item->getWeight());
    }

    public function testSyncTotalPrice(): void
    {
        $item = new OrderItemEntity();
        $item->setUnitPrice(1000);
        $item->setQuantity(3);
        $this->assertSame(3000, $item->getTotalPrice());
    }

    public function testQuantityNonNegative(): void
    {
        $item = new OrderItemEntity();
        $item->setQuantity(-5);
        $this->assertSame(0, $item->getQuantity());
    }

    public function testAttachSnapshot(): void
    {
        $item = new OrderItemEntity();
        $item->setSkuId(100);
        $item->setQuantity(1);
        $item->attachSnapshot([
            'product_id' => 50,
            'product_name' => '快照商品',
            'sku_name' => '快照SKU',
            'sale_price' => 8000,
            'weight' => 2.0,
        ]);
        $this->assertSame(50, $item->getProductId());
        $this->assertSame('快照商品', $item->getProductName());
        $this->assertSame(8000, $item->getUnitPrice());
        $this->assertSame(8000, $item->getTotalPrice());
    }

    public function testToArray(): void
    {
        $item = new OrderItemEntity();
        $item->setProductId(1);
        $item->setSkuId(2);
        $item->setUnitPrice(1000);
        $item->setQuantity(2);
        $arr = $item->toArray();
        $this->assertSame(1, $arr['product_id']);
        $this->assertSame(2, $arr['sku_id']);
        $this->assertSame(1000, $arr['unit_price']);
        $this->assertSame(2000, $arr['total_price']);
    }
}
