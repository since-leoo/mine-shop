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

use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Entity\OrderItemEntity;
use App\Domain\Trade\Order\Entity\OrderShipEntity;
use App\Domain\Trade\Order\Enum\OrderStatus;
use App\Domain\Trade\Order\Enum\PaymentStatus;
use App\Domain\Trade\Order\Enum\ShippingStatus;
use App\Domain\Trade\Order\ValueObject\OrderAddressValue;
use App\Domain\Trade\Order\ValueObject\OrderPriceValue;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class OrderEntityTest extends TestCase
{
    public function testBasicGettersSetters(): void
    {
        $order = $this->makeOrder();
        self::assertSame(1, $order->getId());
        self::assertSame('ORD202603010001', $order->getOrderNo());
        self::assertSame(100, $order->getMemberId());
        self::assertSame('normal', $order->getOrderType());
        self::assertSame('pending', $order->getStatus());
    }

    public function testAddItem(): void
    {
        $order = $this->makeOrder();
        $item = new OrderItemEntity();
        $item->setProductId(1);
        $item->setQuantity(2);
        $order->addItem($item);
        self::assertCount(1, $order->getItems());
    }

    public function testSetAddress(): void
    {
        $order = $this->makeOrder();
        $address = new OrderAddressValue();
        $address->setProvince('浙江省');
        $address->setCity('杭州市');
        $order->setAddress($address);
        self::assertSame('浙江省', $order->getAddress()->getProvince());
    }

    public function testExtras(): void
    {
        $order = $this->makeOrder();
        $order->setExtra('activity_id', 5);
        self::assertSame(5, $order->getExtra('activity_id'));
        self::assertNull($order->getExtra('nonexistent'));
        self::assertSame('default', $order->getExtra('nonexistent', 'default'));
    }

    public function testMarkPaid(): void
    {
        $order = $this->makeOrder('pending', 'pending');
        $order->markPaid();
        self::assertSame(OrderStatus::PAID->value, $order->getStatus());
        self::assertSame(PaymentStatus::PAID->value, $order->getPayStatus());
        self::assertNotNull($order->getPayTime());
    }

    public function testMarkPaidNotPendingThrows(): void
    {
        $order = $this->makeOrder('paid');
        $this->expectException(\RuntimeException::class);
        $order->markPaid();
    }

    public function testShip(): void
    {
        $order = $this->makeOrder('paid');
        $shipEntity = new OrderShipEntity();
        $shipEntity->setPackages([['shipping_company' => '顺丰', 'shipping_no' => 'SF123']]);
        $order->setShipEntity($shipEntity);
        $order->ship();
        self::assertSame(OrderStatus::SHIPPED->value, $order->getStatus());
        self::assertSame(ShippingStatus::SHIPPED->value, $order->getShippingStatus());
    }

    public function testShipNotPaidThrows(): void
    {
        $order = $this->makeOrder('pending');
        $shipEntity = new OrderShipEntity();
        $shipEntity->setPackages([['shipping_company' => '顺丰', 'shipping_no' => 'SF123']]);
        $order->setShipEntity($shipEntity);
        $this->expectException(\RuntimeException::class);
        $order->ship();
    }

    public function testShipNoPackagesThrows(): void
    {
        $order = $this->makeOrder('paid');
        $shipEntity = new OrderShipEntity();
        $shipEntity->setPackages([]);
        $order->setShipEntity($shipEntity);
        $this->expectException(\RuntimeException::class);
        $order->ship();
    }

    public function testComplete(): void
    {
        $order = $this->makeOrder('shipped');
        $order->complete();
        self::assertSame(OrderStatus::COMPLETED->value, $order->getStatus());
        self::assertSame(ShippingStatus::DELIVERED->value, $order->getShippingStatus());
    }

    public function testCompleteNotShippedThrows(): void
    {
        $order = $this->makeOrder('pending');
        $this->expectException(\RuntimeException::class);
        $order->complete();
    }

    public function testCancel(): void
    {
        $order = $this->makeOrder('pending', 'pending');
        $order->cancel();
        self::assertSame(OrderStatus::CANCELLED->value, $order->getStatus());
        self::assertSame(PaymentStatus::CANCELLED->value, $order->getPayStatus());
    }

    public function testCancelPaidOrder(): void
    {
        $order = $this->makeOrder('paid', 'paid');
        $order->cancel();
        self::assertSame(OrderStatus::CANCELLED->value, $order->getStatus());
        self::assertSame(PaymentStatus::PAID->value, $order->getPayStatus());
    }

    public function testCancelShippedThrows(): void
    {
        $order = $this->makeOrder('shipped');
        $this->expectException(\RuntimeException::class);
        $order->cancel();
    }

    public function testVerifyPrice(): void
    {
        $order = $this->makeOrder();
        $pv = new OrderPriceValue();
        $pv->setGoodsAmount(10000);
        $order->setPriceDetail($pv);
        // payAmount = 10000
        $order->verifyPrice(10000); // should not throw
        self::assertTrue(true);
    }

    public function testVerifyPriceMismatchThrows(): void
    {
        $order = $this->makeOrder();
        $pv = new OrderPriceValue();
        $pv->setGoodsAmount(10000);
        $order->setPriceDetail($pv);
        $this->expectException(\DomainException::class);
        $order->verifyPrice(9999);
    }

    public function testSyncPriceDetailFromItems(): void
    {
        $order = $this->makeOrder();
        $item1 = new OrderItemEntity();
        $item1->setTotalPrice(5000);
        $item2 = new OrderItemEntity();
        $item2->setTotalPrice(3000);
        $order->addItem($item1);
        $order->addItem($item2);
        $order->syncPriceDetailFromItems();
        self::assertSame(8000, $order->getPriceDetail()->getGoodsAmount());
    }

    public function testCouponAmount(): void
    {
        $order = $this->makeOrder();
        $order->setCouponAmount(500);
        self::assertSame(500, $order->getCouponAmount());
        $order->setAppliedCouponUserIds([1, 2]);
        self::assertSame([1, 2], $order->getAppliedCouponUserIds());
    }

    public function testToArray(): void
    {
        $order = $this->makeOrder();
        $arr = $order->toArray();
        self::assertSame('ORD202603010001', $arr['order_no']);
        self::assertSame(100, $arr['member_id']);
        self::assertSame('normal', $arr['order_type']);
        self::assertSame('pending', $arr['status']);
    }

    public function testPackageCount(): void
    {
        $order = $this->makeOrder();
        $order->setPackageCount(3);
        self::assertSame(3, $order->getPackageCount());
        $order->setPackageCount(-1);
        self::assertSame(0, $order->getPackageCount());
    }

    private function makeOrder(string $status = 'pending', string $payStatus = 'pending'): OrderEntity
    {
        $order = new OrderEntity();
        $order->setId(1);
        $order->setOrderNo('ORD202603010001');
        $order->setMemberId(100);
        $order->setOrderType('normal');
        $order->setStatus($status);
        $order->setPayStatus($payStatus);
        return $order;
    }
}
