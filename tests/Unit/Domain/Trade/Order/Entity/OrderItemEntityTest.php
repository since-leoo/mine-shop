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

namespace HyperfTests\Unit\Domain\Trade\Order\Entity;

use App\Domain\Trade\Order\Entity\OrderItemEntity;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class OrderItemEntityTest extends TestCase
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
        self::assertSame(100, $item->getSkuId());
        self::assertSame(50, $item->getProductId());
        self::assertSame('商品A', $item->getProductName());
        self::assertSame(2, $item->getQuantity());
        self::assertSame(5000, $item->getUnitPrice());
        self::assertSame(10000, $item->getTotalPrice());
        self::assertSame(1.5, $item->getWeight());
    }

    public function testSyncTotalPrice(): void
    {
        $item = new OrderItemEntity();
        $item->setUnitPrice(1000);
        $item->setQuantity(3);
        self::assertSame(3000, $item->getTotalPrice());
    }

    public function testQuantityNonNegative(): void
    {
        $item = new OrderItemEntity();
        $item->setQuantity(-5);
        self::assertSame(0, $item->getQuantity());
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
        self::assertSame(50, $item->getProductId());
        self::assertSame('快照商品', $item->getProductName());
        self::assertSame(8000, $item->getUnitPrice());
        self::assertSame(8000, $item->getTotalPrice());
    }

    public function testToArray(): void
    {
        $item = new OrderItemEntity();
        $item->setProductId(1);
        $item->setSkuId(2);
        $item->setUnitPrice(1000);
        $item->setQuantity(2);
        $arr = $item->toArray();
        self::assertSame(1, $arr['product_id']);
        self::assertSame(2, $arr['sku_id']);
        self::assertSame(1000, $arr['unit_price']);
        self::assertSame(2000, $arr['total_price']);
    }
}
