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
use App\Domain\Trade\Coupon\Entity\CouponEntity;

/**
 * CouponEntity constructor test.
 * Tests requirement 6.1 and 6.2: Parameterless constructor with default values.
 * @internal
 * @coversNothing
 */
final class CouponEntityConstructorTest extends TestCase
{
    public function testConstructorIsParameterless(): void
    {
        // Should be able to create entity without any parameters
        $entity = new CouponEntity();
        self::assertInstanceOf(CouponEntity::class, $entity);
    }

    public function testConstructorInitializesIdToZero(): void
    {
        $entity = new CouponEntity();
        self::assertSame(0, $entity->getId());
    }

    public function testConstructorInitializesUsedQuantityToZero(): void
    {
        $entity = new CouponEntity();
        self::assertSame(0, $entity->getUsedQuantity());
    }

    public function testConstructorInitializesStatusToActive(): void
    {
        $entity = new CouponEntity();
        self::assertSame('active', $entity->getStatus());
    }

    public function testConstructorInitializesOtherPropertiesToNull(): void
    {
        $entity = new CouponEntity();

        self::assertNull($entity->getName());
        self::assertNull($entity->getType());
        self::assertNull($entity->getValue());
        self::assertNull($entity->getMinAmount());
        self::assertNull($entity->getTotalQuantity());
        self::assertNull($entity->getPerUserLimit());
        self::assertNull($entity->getStartTime());
        self::assertNull($entity->getEndTime());
        self::assertNull($entity->getDescription());
    }

    public function testConstructorInitializesAllDefaultsCorrectly(): void
    {
        $entity = new CouponEntity();

        // Verify all defaults in one comprehensive test
        self::assertSame(0, $entity->getId(), 'id should be 0');
        self::assertSame(0, $entity->getUsedQuantity(), 'usedQuantity should be 0');
        self::assertSame('active', $entity->getStatus(), 'status should be active');

        // All other properties should be null
        self::assertNull($entity->getName(), 'name should be null');
        self::assertNull($entity->getType(), 'type should be null');
        self::assertNull($entity->getValue(), 'value should be null');
        self::assertNull($entity->getMinAmount(), 'minAmount should be null');
        self::assertNull($entity->getTotalQuantity(), 'totalQuantity should be null');
        self::assertNull($entity->getPerUserLimit(), 'perUserLimit should be null');
        self::assertNull($entity->getStartTime(), 'startTime should be null');
        self::assertNull($entity->getEndTime(), 'endTime should be null');
        self::assertNull($entity->getDescription(), 'description should be null');
    }
}
