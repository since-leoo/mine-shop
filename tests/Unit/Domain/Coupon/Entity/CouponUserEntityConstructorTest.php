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

namespace HyperfTests\Unit\Domain\Coupon\Entity;

use PHPUnit\Framework\TestCase;
use Plugin\Since\Coupon\Domain\Entity\CouponUserEntity;

/**
 * CouponUserEntity constructor test.
 * Tests requirement 6.3 and 6.4: Parameterless constructor with default values.
 * @internal
 * @coversNothing
 */
final class CouponUserEntityConstructorTest extends TestCase
{
    public function testConstructorIsParameterless(): void
    {
        // Should be able to create entity without any parameters
        $entity = new CouponUserEntity();
        self::assertInstanceOf(CouponUserEntity::class, $entity);
    }

    public function testConstructorInitializesIdToZero(): void
    {
        $entity = new CouponUserEntity();
        self::assertSame(0, $entity->getId());
    }

    public function testConstructorInitializesStatusToUnused(): void
    {
        $entity = new CouponUserEntity();
        self::assertSame('unused', $entity->getStatus());
    }

    public function testConstructorInitializesOtherPropertiesToNull(): void
    {
        $entity = new CouponUserEntity();

        self::assertNull($entity->getCouponId());
        self::assertNull($entity->getMemberId());
        self::assertNull($entity->getOrderId());
        self::assertNull($entity->getReceivedAt());
        self::assertNull($entity->getUsedAt());
        self::assertNull($entity->getExpireAt());
    }

    public function testConstructorInitializesAllDefaultsCorrectly(): void
    {
        $entity = new CouponUserEntity();

        // Verify all defaults in one comprehensive test
        self::assertSame(0, $entity->getId(), 'id should be 0');
        self::assertSame('unused', $entity->getStatus(), 'status should be unused');

        // All other properties should be null
        self::assertNull($entity->getCouponId(), 'couponId should be null');
        self::assertNull($entity->getMemberId(), 'memberId should be null');
        self::assertNull($entity->getOrderId(), 'orderId should be null');
        self::assertNull($entity->getReceivedAt(), 'receivedAt should be null');
        self::assertNull($entity->getUsedAt(), 'usedAt should be null');
        self::assertNull($entity->getExpireAt(), 'expireAt should be null');
    }
}
