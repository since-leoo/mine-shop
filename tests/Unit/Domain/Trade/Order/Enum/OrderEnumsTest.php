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

namespace HyperfTests\Unit\Domain\Trade\Order\Enum;

use App\Domain\Trade\Order\Enum\OrderStatus;
use App\Domain\Trade\Order\Enum\PaymentStatus;
use App\Domain\Trade\Order\Enum\ShippingStatus;
use App\Domain\Trade\Seckill\Enum\SeckillStatus;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class OrderEnumsTest extends TestCase
{
    public function testOrderStatusValues(): void
    {
        $values = OrderStatus::values();
        self::assertContains('pending', $values);
        self::assertContains('paid', $values);
        self::assertContains('shipped', $values);
        self::assertContains('completed', $values);
        self::assertContains('cancelled', $values);
    }

    public function testPaymentStatusValues(): void
    {
        $values = PaymentStatus::values();
        self::assertContains('pending', $values);
        self::assertContains('paid', $values);
        self::assertContains('failed', $values);
        self::assertContains('cancelled', $values);
    }

    public function testShippingStatusValues(): void
    {
        $values = ShippingStatus::values();
        self::assertContains('pending', $values);
        self::assertContains('shipped', $values);
        self::assertContains('delivered', $values);
    }

    public function testSeckillStatusCases(): void
    {
        self::assertSame('active', SeckillStatus::ACTIVE->value);
        self::assertSame('pending', SeckillStatus::PENDING->value);
        self::assertSame('ended', SeckillStatus::ENDED->value);
        self::assertSame('sold_out', SeckillStatus::SOLD_OUT->value);
        self::assertSame('cancelled', SeckillStatus::CANCELLED->value);
    }

    public function testSeckillStatusFromString(): void
    {
        self::assertSame(SeckillStatus::ACTIVE, SeckillStatus::from('active'));
        self::assertSame(SeckillStatus::PENDING, SeckillStatus::from('pending'));
    }
}
