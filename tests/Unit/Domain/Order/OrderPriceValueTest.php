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

namespace HyperfTests\Unit\Domain\Order;

use App\Domain\Trade\Order\ValueObject\OrderPriceValue;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for OrderPriceValue after float→int refactor.
 *
 * Validates: Requirements 3.3
 *
 * @internal
 * @coversNothing
 */
final class OrderPriceValueTest extends TestCase
{
    public function testDefaultValuesAreZero(): void
    {
        $price = new OrderPriceValue();

        self::assertSame(0, $price->getGoodsAmount());
        self::assertSame(0, $price->getDiscountAmount());
        self::assertSame(0, $price->getShippingFee());
        self::assertSame(0, $price->getTotalAmount());
        self::assertSame(0, $price->getPayAmount());
    }

    public function testSetGoodsAmountRecalculates(): void
    {
        $price = new OrderPriceValue();
        $price->setGoodsAmount(9990);

        self::assertSame(9990, $price->getGoodsAmount());
        self::assertSame(9990, $price->getTotalAmount());
        self::assertSame(9990, $price->getPayAmount());
    }

    public function testSetDiscountAmountRecalculates(): void
    {
        $price = new OrderPriceValue();
        $price->setGoodsAmount(10000);
        $price->setDiscountAmount(2000);

        self::assertSame(10000, $price->getGoodsAmount());
        self::assertSame(2000, $price->getDiscountAmount());
        self::assertSame(8000, $price->getTotalAmount());
        self::assertSame(8000, $price->getPayAmount());
    }

    public function testSetShippingFeeRecalculates(): void
    {
        $price = new OrderPriceValue();
        $price->setGoodsAmount(10000);
        $price->setDiscountAmount(2000);
        $price->setShippingFee(500);

        self::assertSame(8000, $price->getTotalAmount());
        self::assertSame(8500, $price->getPayAmount());
    }

    public function testRecalculateIntegerArithmetic(): void
    {
        $price = new OrderPriceValue();
        // totalAmount = goodsAmount - discountAmount = 9999 - 3333 = 6666
        // payAmount = totalAmount + shippingFee = 6666 + 1111 = 7777
        $price->setGoodsAmount(9999);
        $price->setDiscountAmount(3333);
        $price->setShippingFee(1111);

        self::assertSame(6666, $price->getTotalAmount());
        self::assertSame(7777, $price->getPayAmount());
    }

    public function testSetTotalAmountDirectlyDoesNotRecalculate(): void
    {
        $price = new OrderPriceValue();
        $price->setGoodsAmount(10000);
        $price->setTotalAmount(5000);

        // setTotalAmount is a direct setter, does not trigger recalculate
        self::assertSame(5000, $price->getTotalAmount());
        // payAmount was set by the earlier setGoodsAmount recalculate, not changed by setTotalAmount
        self::assertSame(10000, $price->getPayAmount());
    }

    public function testSetPayAmountDirectly(): void
    {
        $price = new OrderPriceValue();
        $price->setPayAmount(12345);

        self::assertSame(12345, $price->getPayAmount());
    }

    public function testToArrayReturnsIntValues(): void
    {
        $price = new OrderPriceValue();
        $price->setGoodsAmount(10000);
        $price->setDiscountAmount(1500);
        $price->setShippingFee(800);

        $expected = [
            'goods_amount' => 10000,
            'discount_amount' => 1500,
            'shipping_fee' => 800,
            'total_amount' => 8500,
            'pay_amount' => 9300,
        ];

        self::assertSame($expected, $price->toArray());
    }

    public function testZeroDiscountAndShippingFee(): void
    {
        $price = new OrderPriceValue();
        $price->setGoodsAmount(5000);
        $price->setDiscountAmount(0);
        $price->setShippingFee(0);

        self::assertSame(5000, $price->getTotalAmount());
        self::assertSame(5000, $price->getPayAmount());
    }
}
