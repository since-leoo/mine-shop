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

use App\Domain\Coupon\Entity\CouponUserEntity;
use App\Interface\Admin\DTO\Coupon\CouponUserDto;
use PHPUnit\Framework\TestCase;

/**
 * CouponUserEntity create() method test.
 * Tests requirements 4.6, 4.7, 4.8.
 * @internal
 * @coversNothing
 */
final class CouponUserEntityCreateTest extends TestCase
{
    public function testCreateInitializesAllPropertiesFromDto(): void
    {
        // Requirement 4.7: Initialize all properties from DTO
        $dto = new CouponUserDto();
        $dto->couponId = 1;
        $dto->memberId = 100;
        $dto->orderId = 500;
        $dto->status = 'used';
        $dto->receivedAt = '2024-01-01 10:00:00';
        $dto->usedAt = '2024-01-02 15:30:00';
        $dto->expireAt = '2024-12-31 23:59:59';

        $entity = new CouponUserEntity();
        $entity->create($dto);

        self::assertSame(1, $entity->getCouponId());
        self::assertSame(100, $entity->getMemberId());
        self::assertSame(500, $entity->getOrderId());
        self::assertSame('used', $entity->getStatus());
        self::assertSame('2024-01-01 10:00:00', $entity->getReceivedAt());
        self::assertSame('2024-01-02 15:30:00', $entity->getUsedAt());
        self::assertSame('2024-12-31 23:59:59', $entity->getExpireAt());
    }

    public function testCreateSetsStatusToUnusedWhenNotProvided(): void
    {
        // Requirement 4.6: Set status='unused' as default if not provided
        $dto = new CouponUserDto();
        $dto->couponId = 1;
        $dto->memberId = 100;
        $dto->receivedAt = '2024-01-01 10:00:00';
        $dto->expireAt = '2024-12-31 23:59:59';
        // status is null

        $entity = new CouponUserEntity();
        $entity->create($dto);

        self::assertSame('unused', $entity->getStatus());
    }

    public function testCreateReturnsSelfForMethodChaining(): void
    {
        // Requirement 4.8: Return $this for method chaining
        $dto = new CouponUserDto();
        $dto->couponId = 1;
        $dto->memberId = 100;
        $dto->receivedAt = '2024-01-01 10:00:00';
        $dto->expireAt = '2024-12-31 23:59:59';

        $entity = new CouponUserEntity();
        $result = $entity->create($dto);

        self::assertSame($entity, $result);
    }

    public function testCreateWithMinimalRequiredFields(): void
    {
        // Test with only required fields
        $dto = new CouponUserDto();
        $dto->couponId = 1;
        $dto->memberId = 100;
        $dto->receivedAt = '2024-01-01 10:00:00';
        $dto->expireAt = '2024-12-31 23:59:59';

        $entity = new CouponUserEntity();
        $entity->create($dto);

        self::assertSame(1, $entity->getCouponId());
        self::assertSame(100, $entity->getMemberId());
        self::assertSame('2024-01-01 10:00:00', $entity->getReceivedAt());
        self::assertSame('2024-12-31 23:59:59', $entity->getExpireAt());
        self::assertSame('unused', $entity->getStatus());
        self::assertNull($entity->getOrderId());
        self::assertNull($entity->getUsedAt());
    }

    public function testCreateWithNullOptionalFields(): void
    {
        // Test that null optional fields are handled correctly
        $dto = new CouponUserDto();
        $dto->couponId = 1;
        $dto->memberId = 100;
        $dto->orderId = null;
        $dto->receivedAt = '2024-01-01 10:00:00';
        $dto->usedAt = null;
        $dto->expireAt = '2024-12-31 23:59:59';

        $entity = new CouponUserEntity();
        $entity->create($dto);

        self::assertNull($entity->getOrderId());
        self::assertNull($entity->getUsedAt());
    }

    public function testCreateAllowsMethodChaining(): void
    {
        // Test that method chaining works
        $dto = new CouponUserDto();
        $dto->couponId = 1;
        $dto->memberId = 100;
        $dto->receivedAt = '2024-01-01 10:00:00';
        $dto->expireAt = '2024-12-31 23:59:59';

        $entity = new CouponUserEntity();
        $result = $entity->create($dto)->setOrderId(999);

        self::assertInstanceOf(CouponUserEntity::class, $result);
        self::assertSame(999, $entity->getOrderId());
    }

    public function testCreateWithExplicitUnusedStatus(): void
    {
        // Test that explicit 'unused' status is preserved
        $dto = new CouponUserDto();
        $dto->couponId = 1;
        $dto->memberId = 100;
        $dto->status = 'unused';
        $dto->receivedAt = '2024-01-01 10:00:00';
        $dto->expireAt = '2024-12-31 23:59:59';

        $entity = new CouponUserEntity();
        $entity->create($dto);

        self::assertSame('unused', $entity->getStatus());
    }

    public function testCreateWithExpiredStatus(): void
    {
        // Test that other status values are preserved
        $dto = new CouponUserDto();
        $dto->couponId = 1;
        $dto->memberId = 100;
        $dto->status = 'expired';
        $dto->receivedAt = '2024-01-01 10:00:00';
        $dto->expireAt = '2024-01-31 23:59:59';

        $entity = new CouponUserEntity();
        $entity->create($dto);

        self::assertSame('expired', $entity->getStatus());
    }

    public function testCreateWithAllFieldsPopulated(): void
    {
        // Comprehensive test with all fields
        $dto = new CouponUserDto();
        $dto->couponId = 42;
        $dto->memberId = 999;
        $dto->orderId = 1234;
        $dto->status = 'used';
        $dto->receivedAt = '2024-01-15 08:30:00';
        $dto->usedAt = '2024-01-20 14:45:00';
        $dto->expireAt = '2024-06-30 23:59:59';

        $entity = new CouponUserEntity();
        $entity->create($dto);

        self::assertSame(42, $entity->getCouponId());
        self::assertSame(999, $entity->getMemberId());
        self::assertSame(1234, $entity->getOrderId());
        self::assertSame('used', $entity->getStatus());
        self::assertSame('2024-01-15 08:30:00', $entity->getReceivedAt());
        self::assertSame('2024-01-20 14:45:00', $entity->getUsedAt());
        self::assertSame('2024-06-30 23:59:59', $entity->getExpireAt());
    }

    public function testCreateDoesNotModifyDtoObject(): void
    {
        // Ensure the DTO is not modified by create()
        $dto = new CouponUserDto();
        $dto->couponId = 1;
        $dto->memberId = 100;
        $dto->receivedAt = '2024-01-01 10:00:00';
        $dto->expireAt = '2024-12-31 23:59:59';

        $originalCouponId = $dto->couponId;
        $originalMemberId = $dto->memberId;

        $entity = new CouponUserEntity();
        $entity->create($dto);

        self::assertSame($originalCouponId, $dto->couponId);
        self::assertSame($originalMemberId, $dto->memberId);
    }
}
