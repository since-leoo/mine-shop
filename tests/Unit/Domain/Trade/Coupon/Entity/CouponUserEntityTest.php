<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\Coupon\Entity;

use App\Domain\Trade\Coupon\Entity\CouponUserEntity;
use PHPUnit\Framework\TestCase;

class CouponUserEntityTest extends TestCase
{
    public function testIssue(): void
    {
        $entity = CouponUserEntity::issue(1, 100, '2026-03-01 00:00:00', '2026-03-31 23:59:59');
        $this->assertSame(1, $entity->getCouponId());
        $this->assertSame(100, $entity->getMemberId());
        $this->assertSame('unused', $entity->getStatus());
        $this->assertSame('2026-03-01 00:00:00', $entity->getReceivedAt());
        $this->assertSame('2026-03-31 23:59:59', $entity->getExpireAt());
    }

    public function testMarkUsed(): void
    {
        $entity = CouponUserEntity::issue(1, 100, '2026-03-01 00:00:00', '2026-03-31 23:59:59');
        $entity->markUsed('2026-03-15 12:00:00', 200);
        $this->assertSame('used', $entity->getStatus());
        $this->assertSame('2026-03-15 12:00:00', $entity->getUsedAt());
        $this->assertSame(200, $entity->getOrderId());
    }

    public function testMarkUsedAlreadyUsedThrows(): void
    {
        $entity = CouponUserEntity::issue(1, 100, '2026-03-01 00:00:00', '2026-03-31 23:59:59');
        $entity->markUsed();
        $this->expectException(\RuntimeException::class);
        $entity->markUsed();
    }

    public function testMarkExpired(): void
    {
        $entity = CouponUserEntity::issue(1, 100, '2026-03-01 00:00:00', '2026-03-31 23:59:59');
        $entity->markExpired();
        $this->assertSame('expired', $entity->getStatus());
    }

    public function testMarkExpiredAlreadyUsedThrows(): void
    {
        $entity = CouponUserEntity::issue(1, 100, '2026-03-01 00:00:00', '2026-03-31 23:59:59');
        $entity->markUsed();
        $this->expectException(\RuntimeException::class);
        $entity->markExpired();
    }

    public function testToArray(): void
    {
        $entity = CouponUserEntity::issue(1, 100, '2026-03-01 00:00:00', '2026-03-31 23:59:59');
        $arr = $entity->toArray();
        $this->assertSame(1, $arr['coupon_id']);
        $this->assertSame(100, $arr['member_id']);
        $this->assertSame('unused', $arr['status']);
    }

    public function testSettersChaining(): void
    {
        $entity = new CouponUserEntity();
        $entity->setId(5)->setCouponId(10)->setMemberId(20)->setStatus('used');
        $this->assertSame(5, $entity->getId());
        $this->assertSame(10, $entity->getCouponId());
        $this->assertSame(20, $entity->getMemberId());
        $this->assertSame('used', $entity->getStatus());
    }
}
