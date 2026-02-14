<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\Coupon\Entity;

use App\Domain\Trade\Coupon\Entity\CouponEntity;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class CouponEntityTest extends TestCase
{
    private function makeCoupon(): CouponEntity
    {
        $c = new CouponEntity();
        $c->setId(1);
        $c->setName('满减券');
        $c->setType('fixed');
        $c->setValue(1000);
        $c->setMinAmount(5000);
        $c->setTotalQuantity(100);
        $c->setUsedQuantity(0);
        $c->setPerUserLimit(3);
        $c->setStartTime('2026-03-01 00:00:00');
        $c->setEndTime('2026-03-31 23:59:59');
        $c->setStatus('active');
        return $c;
    }

    public function testConstructorDefaults(): void
    {
        $c = new CouponEntity();
        $this->assertSame(0, $c->getId());
        $this->assertSame('active', $c->getStatus());
        $this->assertSame(0, $c->getUsedQuantity());
    }

    public function testGettersSetters(): void
    {
        $c = $this->makeCoupon();
        $this->assertSame(1, $c->getId());
        $this->assertSame('满减券', $c->getName());
        $this->assertSame('fixed', $c->getType());
        $this->assertSame(1000, $c->getValue());
        $this->assertSame(5000, $c->getMinAmount());
        $this->assertSame(100, $c->getTotalQuantity());
        $this->assertSame(3, $c->getPerUserLimit());
    }

    public function testActivateDeactivate(): void
    {
        $c = $this->makeCoupon();
        $c->deactivate();
        $this->assertSame('inactive', $c->getStatus());
        $c->activate();
        $this->assertSame('active', $c->getStatus());
    }

    public function testToggleStatus(): void
    {
        $c = $this->makeCoupon();
        $c->toggleStatus();
        $this->assertSame('inactive', $c->getStatus());
        $c->toggleStatus();
        $this->assertSame('active', $c->getStatus());
    }

    public function testAssertActive(): void
    {
        $c = $this->makeCoupon();
        $c->assertActive(); // should not throw
        $this->assertTrue(true);
    }

    public function testAssertActiveInactiveThrows(): void
    {
        $c = $this->makeCoupon();
        $c->deactivate();
        $this->expectException(\RuntimeException::class);
        $c->assertActive();
    }

    public function testAssertEffectiveAt(): void
    {
        $c = $this->makeCoupon();
        $now = Carbon::parse('2026-03-15 12:00:00');
        $c->assertEffectiveAt($now); // should not throw
        $this->assertTrue(true);
    }

    public function testAssertEffectiveAtNotStarted(): void
    {
        $c = $this->makeCoupon();
        $now = Carbon::parse('2026-02-01 00:00:00');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('优惠券未开始');
        $c->assertEffectiveAt($now);
    }

    public function testAssertEffectiveAtExpired(): void
    {
        $c = $this->makeCoupon();
        $now = Carbon::parse('2026-04-01 00:00:00');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('优惠券已过期');
        $c->assertEffectiveAt($now);
    }

    public function testCanMemberReceive(): void
    {
        $c = $this->makeCoupon(); // perUserLimit = 3
        $this->assertTrue($c->canMemberReceive(0));
        $this->assertTrue($c->canMemberReceive(2));
        $this->assertFalse($c->canMemberReceive(3));
    }

    public function testCanMemberReceiveNoLimit(): void
    {
        $c = $this->makeCoupon();
        $c->setPerUserLimit(null);
        $this->assertTrue($c->canMemberReceive(999));
    }

    public function testDefineTimeWindowInvalid(): void
    {
        $c = $this->makeCoupon();
        $this->expectException(\InvalidArgumentException::class);
        $c->defineTimeWindow('2026-03-31 00:00:00', '2026-03-01 00:00:00');
    }

    public function testCalculateDiscountFixed(): void
    {
        $c = $this->makeCoupon();
        $c->setType('fixed');
        $c->setValue(1000);
        $this->assertSame(1000, $c->calculateDiscount(10000));
    }

    public function testCalculateDiscountPercent(): void
    {
        $c = $this->makeCoupon();
        $c->setType('percent');
        $c->setValue(850); // 8.5折
        $discount = $c->calculateDiscount(10000);
        // 10000 - round(10000 * 850 / 1000) = 10000 - 8500 = 1500
        $this->assertSame(1500, $discount);
    }

    public function testResolveExpireAt(): void
    {
        $c = $this->makeCoupon();
        $now = Carbon::parse('2026-03-15 00:00:00');
        $expire = $c->resolveExpireAt('2026-03-20 00:00:00', $now);
        $this->assertSame('2026-03-20', $expire->toDateString());
    }

    public function testResolveExpireAtPastThrows(): void
    {
        $c = $this->makeCoupon();
        $now = Carbon::parse('2026-03-15 00:00:00');
        $this->expectException(\InvalidArgumentException::class);
        $c->resolveExpireAt('2026-03-10 00:00:00', $now);
    }

    public function testToArray(): void
    {
        $c = $this->makeCoupon();
        $arr = $c->toArray();
        $this->assertSame('满减券', $arr['name']);
        $this->assertSame('fixed', $arr['type']);
        $this->assertSame(1000, $arr['value']);
        $this->assertSame('active', $arr['status']);
    }
}
