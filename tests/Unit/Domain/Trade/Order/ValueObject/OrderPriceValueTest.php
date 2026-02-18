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

namespace HyperfTests\Unit\Domain\Trade\Order\ValueObject;

use App\Domain\Trade\Order\ValueObject\OrderPriceValue;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class OrderPriceValueTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $pv = new OrderPriceValue();
        self::assertSame(0, $pv->getGoodsAmount());
        self::assertSame(0, $pv->getDiscountAmount());
        self::assertSame(0, $pv->getShippingFee());
        self::assertSame(0, $pv->getTotalAmount());
        self::assertSame(0, $pv->getPayAmount());
    }

    public function testRecalculateOnSetGoodsAmount(): void
    {
        $pv = new OrderPriceValue();
        $pv->setGoodsAmount(10000);
        self::assertSame(10000, $pv->getTotalAmount());
        self::assertSame(10000, $pv->getPayAmount());
    }

    public function testRecalculateWithDiscount(): void
    {
        $pv = new OrderPriceValue();
        $pv->setGoodsAmount(10000);
        $pv->setDiscountAmount(2000);
        self::assertSame(8000, $pv->getTotalAmount());
        self::assertSame(8000, $pv->getPayAmount());
    }

    public function testRecalculateWithShipping(): void
    {
        $pv = new OrderPriceValue();
        $pv->setGoodsAmount(10000);
        $pv->setDiscountAmount(2000);
        $pv->setShippingFee(500);
        self::assertSame(8000, $pv->getTotalAmount());
        self::assertSame(8500, $pv->getPayAmount());
    }

    public function testToArray(): void
    {
        $pv = new OrderPriceValue();
        $pv->setGoodsAmount(10000);
        $pv->setDiscountAmount(1000);
        $pv->setShippingFee(500);
        $arr = $pv->toArray();
        self::assertSame(10000, $arr['goods_amount']);
        self::assertSame(1000, $arr['discount_amount']);
        self::assertSame(500, $arr['shipping_fee']);
        self::assertSame(9000, $arr['total_amount']);
        self::assertSame(9500, $arr['pay_amount']);
    }
}
