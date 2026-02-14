<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\Order\Enum;

use App\Domain\Trade\Order\Enum\OrderStatus;
use App\Domain\Trade\Order\Enum\PaymentStatus;
use App\Domain\Trade\Order\Enum\ShippingStatus;
use App\Domain\Trade\Seckill\Enum\SeckillStatus;
use PHPUnit\Framework\TestCase;

class OrderEnumsTest extends TestCase
{
    public function testOrderStatusValues(): void
    {
        $values = OrderStatus::values();
        $this->assertContains('pending', $values);
        $this->assertContains('paid', $values);
        $this->assertContains('shipped', $values);
        $this->assertContains('completed', $values);
        $this->assertContains('cancelled', $values);
    }

    public function testPaymentStatusValues(): void
    {
        $values = PaymentStatus::values();
        $this->assertContains('pending', $values);
        $this->assertContains('paid', $values);
        $this->assertContains('failed', $values);
        $this->assertContains('cancelled', $values);
    }

    public function testShippingStatusValues(): void
    {
        $values = ShippingStatus::values();
        $this->assertContains('pending', $values);
        $this->assertContains('shipped', $values);
        $this->assertContains('delivered', $values);
    }

    public function testSeckillStatusCases(): void
    {
        $this->assertSame('active', SeckillStatus::ACTIVE->value);
        $this->assertSame('pending', SeckillStatus::PENDING->value);
        $this->assertSame('ended', SeckillStatus::ENDED->value);
        $this->assertSame('sold_out', SeckillStatus::SOLD_OUT->value);
        $this->assertSame('cancelled', SeckillStatus::CANCELLED->value);
    }

    public function testSeckillStatusFromString(): void
    {
        $this->assertSame(SeckillStatus::ACTIVE, SeckillStatus::from('active'));
        $this->assertSame(SeckillStatus::PENDING, SeckillStatus::from('pending'));
    }
}
