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

namespace HyperfTests\Unit\Domain\Trade\Coupon\Entity;

use App\Domain\Trade\Coupon\Entity\CouponUserEntity;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class CouponUserEntityTest extends TestCase
{
    public function testIssue(): void
    {
        $entity = CouponUserEntity::issue(1, 100, '2026-03-01 00:00:00', '2026-03-31 23:59:59');
        self::assertSame(1, $entity->getCouponId());
        self::assertSame(100, $entity->getMemberId());
        self::assertSame('unused', $entity->getStatus());
        self::assertSame('2026-03-01 00:00:00', $entity->getReceivedAt());
        self::assertSame('2026-03-31 23:59:59', $entity->getExpireAt());
    }

    public function testMarkUsed(): void
    {
        $entity = CouponUserEntity::issue(1, 100, '2026-03-01 00:00:00', '2026-03-31 23:59:59');
        $entity->markUsed('2026-03-15 12:00:00', 200);
        self::assertSame('used', $entity->getStatus());
        self::assertSame('2026-03-15 12:00:00', $entity->getUsedAt());
        self::assertSame(200, $entity->getOrderId());
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
        self::assertSame('expired', $entity->getStatus());
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
        self::assertSame(1, $arr['coupon_id']);
        self::assertSame(100, $arr['member_id']);
        self::assertSame('unused', $arr['status']);
    }

    public function testSettersChaining(): void
    {
        $entity = new CouponUserEntity();
        $entity->setId(5)->setCouponId(10)->setMemberId(20)->setStatus('used');
        self::assertSame(5, $entity->getId());
        self::assertSame(10, $entity->getCouponId());
        self::assertSame(20, $entity->getMemberId());
        self::assertSame('used', $entity->getStatus());
    }
}
