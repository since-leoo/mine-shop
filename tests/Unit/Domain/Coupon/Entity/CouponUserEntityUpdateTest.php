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
use App\Domain\Trade\Coupon\Entity\CouponUserEntity;
use App\Interface\Admin\Dto\Coupon\CouponUserDto;

/**
 * CouponUserEntity update() method test.
 * Tests requirements 5.4, 5.5, 5.6.
 * @internal
 * @coversNothing
 */
final class CouponUserEntityUpdateTest extends TestCase
{
    public function testUpdateOnlyModifiesNonNullProperties(): void
    {
        // Requirement 5.5: Only update properties that are non-null in the DTO
        $entity = new CouponUserEntity();
        $entity->setCouponId(1)
            ->setMemberId(100)
            ->setOrderId(500)
            ->setStatus('unused')
            ->setReceivedAt('2024-01-01 10:00:00')
            ->setUsedAt(null)
            ->setExpireAt('2024-12-31 23:59:59');

        // Update with partial DTO (only some fields are non-null)
        $dto = new CouponUserDto();
        $dto->couponId = null; // Should not update
        $dto->memberId = 200; // Should update
        $dto->orderId = null; // Should not update
        $dto->status = 'used'; // Should update
        $dto->receivedAt = null; // Should not update
        $dto->usedAt = '2024-01-02 15:30:00'; // Should update
        $dto->expireAt = null; // Should not update

        $entity->update($dto);

        // Verify only non-null DTO properties were updated
        self::assertSame(1, $entity->getCouponId(), 'couponId should not change when DTO value is null');
        self::assertSame(200, $entity->getMemberId(), 'memberId should be updated');
        self::assertSame(500, $entity->getOrderId(), 'orderId should not change when DTO value is null');
        self::assertSame('used', $entity->getStatus(), 'status should be updated');
        self::assertSame('2024-01-01 10:00:00', $entity->getReceivedAt(), 'receivedAt should not change when DTO value is null');
        self::assertSame('2024-01-02 15:30:00', $entity->getUsedAt(), 'usedAt should be updated');
        self::assertSame('2024-12-31 23:59:59', $entity->getExpireAt(), 'expireAt should not change when DTO value is null');
    }

    public function testUpdateReturnsSelfForMethodChaining(): void
    {
        // Requirement 5.6: Return $this for method chaining
        $entity = new CouponUserEntity();
        $entity->setCouponId(1)->setMemberId(100);

        $dto = new CouponUserDto();
        $dto->status = 'used';

        $result = $entity->update($dto);

        self::assertSame($entity, $result);
    }

    public function testUpdateWithAllNullProperties(): void
    {
        // Test that entity remains unchanged when all DTO properties are null
        $entity = new CouponUserEntity();
        $entity->setCouponId(1)
            ->setMemberId(100)
            ->setOrderId(500)
            ->setStatus('unused')
            ->setReceivedAt('2024-01-01 10:00:00')
            ->setUsedAt(null)
            ->setExpireAt('2024-12-31 23:59:59');

        $dto = new CouponUserDto();
        // All properties are null

        $entity->update($dto);

        // Verify nothing changed
        self::assertSame(1, $entity->getCouponId());
        self::assertSame(100, $entity->getMemberId());
        self::assertSame(500, $entity->getOrderId());
        self::assertSame('unused', $entity->getStatus());
        self::assertSame('2024-01-01 10:00:00', $entity->getReceivedAt());
        self::assertNull($entity->getUsedAt());
        self::assertSame('2024-12-31 23:59:59', $entity->getExpireAt());
    }

    public function testUpdateWithAllNonNullProperties(): void
    {
        // Test that all properties are updated when all DTO properties are non-null
        $entity = new CouponUserEntity();
        $entity->setCouponId(1)
            ->setMemberId(100)
            ->setOrderId(500)
            ->setStatus('unused')
            ->setReceivedAt('2024-01-01 10:00:00')
            ->setUsedAt(null)
            ->setExpireAt('2024-12-31 23:59:59');

        $dto = new CouponUserDto();
        $dto->couponId = 2;
        $dto->memberId = 200;
        $dto->orderId = 600;
        $dto->status = 'used';
        $dto->receivedAt = '2024-02-01 12:00:00';
        $dto->usedAt = '2024-02-02 14:00:00';
        $dto->expireAt = '2024-11-30 23:59:59';

        $entity->update($dto);

        // Verify all properties were updated
        self::assertSame(2, $entity->getCouponId());
        self::assertSame(200, $entity->getMemberId());
        self::assertSame(600, $entity->getOrderId());
        self::assertSame('used', $entity->getStatus());
        self::assertSame('2024-02-01 12:00:00', $entity->getReceivedAt());
        self::assertSame('2024-02-02 14:00:00', $entity->getUsedAt());
        self::assertSame('2024-11-30 23:59:59', $entity->getExpireAt());
    }

    public function testUpdateAllowsMethodChaining(): void
    {
        // Test that method chaining works with update()
        $entity = new CouponUserEntity();
        $entity->setCouponId(1)->setMemberId(100);

        $dto = new CouponUserDto();
        $dto->status = 'used';
        $dto->orderId = 999;

        $result = $entity->update($dto)->setExpireAt('2024-12-31 23:59:59');

        self::assertInstanceOf(CouponUserEntity::class, $result);
        self::assertSame('used', $entity->getStatus());
        self::assertSame(999, $entity->getOrderId());
        self::assertSame('2024-12-31 23:59:59', $entity->getExpireAt());
    }

    public function testUpdateCouponIdOnly(): void
    {
        // Test updating only couponId
        $entity = new CouponUserEntity();
        $entity->setCouponId(1)->setMemberId(100)->setStatus('unused');

        $dto = new CouponUserDto();
        $dto->couponId = 5;

        $entity->update($dto);

        self::assertSame(5, $entity->getCouponId());
        self::assertSame(100, $entity->getMemberId());
        self::assertSame('unused', $entity->getStatus());
    }

    public function testUpdateMemberIdOnly(): void
    {
        // Test updating only memberId
        $entity = new CouponUserEntity();
        $entity->setCouponId(1)->setMemberId(100)->setStatus('unused');

        $dto = new CouponUserDto();
        $dto->memberId = 300;

        $entity->update($dto);

        self::assertSame(1, $entity->getCouponId());
        self::assertSame(300, $entity->getMemberId());
        self::assertSame('unused', $entity->getStatus());
    }

    public function testUpdateOrderIdOnly(): void
    {
        // Test updating only orderId
        $entity = new CouponUserEntity();
        $entity->setCouponId(1)->setMemberId(100)->setOrderId(null);

        $dto = new CouponUserDto();
        $dto->orderId = 777;

        $entity->update($dto);

        self::assertSame(1, $entity->getCouponId());
        self::assertSame(100, $entity->getMemberId());
        self::assertSame(777, $entity->getOrderId());
    }

    public function testUpdateStatusOnly(): void
    {
        // Test updating only status
        $entity = new CouponUserEntity();
        $entity->setCouponId(1)->setMemberId(100)->setStatus('unused');

        $dto = new CouponUserDto();
        $dto->status = 'expired';

        $entity->update($dto);

        self::assertSame(1, $entity->getCouponId());
        self::assertSame(100, $entity->getMemberId());
        self::assertSame('expired', $entity->getStatus());
    }

    public function testUpdateReceivedAtOnly(): void
    {
        // Test updating only receivedAt
        $entity = new CouponUserEntity();
        $entity->setCouponId(1)->setMemberId(100)->setReceivedAt('2024-01-01 10:00:00');

        $dto = new CouponUserDto();
        $dto->receivedAt = '2024-02-01 12:00:00';

        $entity->update($dto);

        self::assertSame(1, $entity->getCouponId());
        self::assertSame(100, $entity->getMemberId());
        self::assertSame('2024-02-01 12:00:00', $entity->getReceivedAt());
    }

    public function testUpdateUsedAtOnly(): void
    {
        // Test updating only usedAt
        $entity = new CouponUserEntity();
        $entity->setCouponId(1)->setMemberId(100)->setUsedAt(null);

        $dto = new CouponUserDto();
        $dto->usedAt = '2024-01-15 14:30:00';

        $entity->update($dto);

        self::assertSame(1, $entity->getCouponId());
        self::assertSame(100, $entity->getMemberId());
        self::assertSame('2024-01-15 14:30:00', $entity->getUsedAt());
    }

    public function testUpdateExpireAtOnly(): void
    {
        // Test updating only expireAt
        $entity = new CouponUserEntity();
        $entity->setCouponId(1)->setMemberId(100)->setExpireAt('2024-12-31 23:59:59');

        $dto = new CouponUserDto();
        $dto->expireAt = '2024-06-30 23:59:59';

        $entity->update($dto);

        self::assertSame(1, $entity->getCouponId());
        self::assertSame(100, $entity->getMemberId());
        self::assertSame('2024-06-30 23:59:59', $entity->getExpireAt());
    }

    public function testUpdateMultipleProperties(): void
    {
        // Test updating multiple properties at once
        $entity = new CouponUserEntity();
        $entity->setCouponId(1)
            ->setMemberId(100)
            ->setOrderId(null)
            ->setStatus('unused')
            ->setReceivedAt('2024-01-01 10:00:00')
            ->setUsedAt(null)
            ->setExpireAt('2024-12-31 23:59:59');

        $dto = new CouponUserDto();
        $dto->status = 'used';
        $dto->orderId = 888;
        $dto->usedAt = '2024-01-10 16:45:00';

        $entity->update($dto);

        // Verify updated properties
        self::assertSame('used', $entity->getStatus());
        self::assertSame(888, $entity->getOrderId());
        self::assertSame('2024-01-10 16:45:00', $entity->getUsedAt());

        // Verify unchanged properties
        self::assertSame(1, $entity->getCouponId());
        self::assertSame(100, $entity->getMemberId());
        self::assertSame('2024-01-01 10:00:00', $entity->getReceivedAt());
        self::assertSame('2024-12-31 23:59:59', $entity->getExpireAt());
    }

    public function testUpdateDoesNotModifyDtoObject(): void
    {
        // Ensure the DTO is not modified by update()
        $entity = new CouponUserEntity();
        $entity->setCouponId(1)->setMemberId(100);

        $dto = new CouponUserDto();
        $dto->memberId = 200;
        $dto->status = 'used';

        $originalMemberId = $dto->memberId;
        $originalStatus = $dto->status;

        $entity->update($dto);

        self::assertSame($originalMemberId, $dto->memberId);
        self::assertSame($originalStatus, $dto->status);
    }

    public function testUpdatePreservesNullValues(): void
    {
        // Test that null values in entity are preserved when DTO has null
        $entity = new CouponUserEntity();
        $entity->setCouponId(1)
            ->setMemberId(100)
            ->setOrderId(null)
            ->setUsedAt(null);

        $dto = new CouponUserDto();
        $dto->status = 'unused';
        // orderId and usedAt are null in DTO

        $entity->update($dto);

        self::assertNull($entity->getOrderId());
        self::assertNull($entity->getUsedAt());
        self::assertSame('unused', $entity->getStatus());
    }

    public function testUpdateCanSetNullToValue(): void
    {
        // Test that we can update a null value to a non-null value
        $entity = new CouponUserEntity();
        $entity->setCouponId(1)
            ->setMemberId(100)
            ->setOrderId(null)
            ->setUsedAt(null);

        $dto = new CouponUserDto();
        $dto->orderId = 999;
        $dto->usedAt = '2024-01-15 10:00:00';

        $entity->update($dto);

        self::assertSame(999, $entity->getOrderId());
        self::assertSame('2024-01-15 10:00:00', $entity->getUsedAt());
    }

    public function testUpdateSequentialCalls(): void
    {
        // Test that multiple update calls work correctly
        $entity = new CouponUserEntity();
        $entity->setCouponId(1)
            ->setMemberId(100)
            ->setStatus('unused');

        // First update
        $dto1 = new CouponUserDto();
        $dto1->orderId = 500;
        $entity->update($dto1);

        self::assertSame(500, $entity->getOrderId());
        self::assertSame('unused', $entity->getStatus());

        // Second update
        $dto2 = new CouponUserDto();
        $dto2->status = 'used';
        $dto2->usedAt = '2024-01-15 10:00:00';
        $entity->update($dto2);

        self::assertSame(500, $entity->getOrderId());
        self::assertSame('used', $entity->getStatus());
        self::assertSame('2024-01-15 10:00:00', $entity->getUsedAt());
    }
}
