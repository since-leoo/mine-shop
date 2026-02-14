<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\Order\ValueObject;

use App\Domain\Trade\Order\ValueObject\OrderPriceValue;
use PHPUnit\Framework\TestCase;

class OrderPriceValueTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $pv = new OrderPriceValue();
        $this->assertSame(0, $pv->getGoodsAmount());
        $this->assertSame(0, $pv->getDiscountAmount());
        $this->assertSame(0, $pv->getShippingFee());
        $this->assertSame(0, $pv->getTotalAmount());
        $this->assertSame(0, $pv->getPayAmount());
    }

    public function testRecalculateOnSetGoodsAmount(): void
    {
        $pv = new OrderPriceValue();
        $pv->setGoodsAmount(10000);
        $this->assertSame(10000, $pv->getTotalAmount());
        $this->assertSame(10000, $pv->getPayAmount());
    }

    public function testRecalculateWithDiscount(): void
    {
        $pv = new OrderPriceValue();
        $pv->setGoodsAmount(10000);
        $pv->setDiscountAmount(2000);
        $this->assertSame(8000, $pv->getTotalAmount());
        $this->assertSame(8000, $pv->getPayAmount());
    }

    public function testRecalculateWithShipping(): void
    {
        $pv = new OrderPriceValue();
        $pv->setGoodsAmount(10000);
        $pv->setDiscountAmount(2000);
        $pv->setShippingFee(500);
        $this->assertSame(8000, $pv->getTotalAmount());
        $this->assertSame(8500, $pv->getPayAmount());
    }

    public function testToArray(): void
    {
        $pv = new OrderPriceValue();
        $pv->setGoodsAmount(10000);
        $pv->setDiscountAmount(1000);
        $pv->setShippingFee(500);
        $arr = $pv->toArray();
        $this->assertSame(10000, $arr['goods_amount']);
        $this->assertSame(1000, $arr['discount_amount']);
        $this->assertSame(500, $arr['shipping_fee']);
        $this->assertSame(9000, $arr['total_amount']);
        $this->assertSame(9500, $arr['pay_amount']);
    }
}
