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
use App\Interface\Admin\Dto\Coupon\CouponDto;

/**
 * CouponEntity update() method test.
 * Tests requirements 5.1, 5.2, 5.3.
 * @internal
 * @coversNothing
 */
final class CouponEntityUpdateTest extends TestCase
{
    public function testUpdateOnlyModifiesNonNullProperties(): void
    {
        // Requirement 5.2: Only update properties that are non-null in the DTO

        // Create entity with initial values
        $createDto = new CouponDto();
        $createDto->name = 'Original Name';
        $createDto->type = 'fixed';
        $createDto->value = 10.0;
        $createDto->minAmount = 50.0;
        $createDto->totalQuantity = 100;
        $createDto->perUserLimit = 5;
        $createDto->startTime = '2024-01-01 00:00:00';
        $createDto->endTime = '2024-12-31 23:59:59';
        $createDto->description = 'Original description';

        $entity = new CouponEntity();
        $entity->create($createDto);

        // Update with partial DTO (only name and value are non-null)
        $updateDto = new CouponDto();
        $updateDto->name = 'Updated Name';
        $updateDto->value = 20.0;
        // All other properties are null

        $entity->update($updateDto);

        // Verify only non-null properties were updated
        self::assertSame('Updated Name', $entity->getName());
        self::assertSame(20.0, $entity->getValue());

        // Verify null properties were NOT updated (kept original values)
        self::assertSame('fixed', $entity->getType());
        self::assertSame(50.0, $entity->getMinAmount());
        self::assertSame(100, $entity->getTotalQuantity());
        self::assertSame(5, $entity->getPerUserLimit());
        self::assertSame('2024-01-01 00:00:00', $entity->getStartTime());
        self::assertSame('2024-12-31 23:59:59', $entity->getEndTime());
        self::assertSame('active', $entity->getStatus());
        self::assertSame('Original description', $entity->getDescription());
    }

    public function testUpdateReturnsSelfForMethodChaining(): void
    {
        // Requirement 5.3: Return $this for method chaining
        $createDto = new CouponDto();
        $createDto->name = 'Test Coupon';
        $createDto->type = 'fixed';
        $createDto->value = 10.0;
        $createDto->totalQuantity = 100;
        $createDto->startTime = '2024-01-01 00:00:00';
        $createDto->endTime = '2024-12-31 23:59:59';

        $entity = new CouponEntity();
        $entity->create($createDto);

        $updateDto = new CouponDto();
        $updateDto->name = 'Updated Name';

        $result = $entity->update($updateDto);

        self::assertSame($entity, $result);
    }

    public function testUpdateValidatesTimeWindowWhenStartTimeUpdated(): void
    {
        // Requirement 5.1: Validate time window if startTime or endTime are updated
        $createDto = new CouponDto();
        $createDto->name = 'Test Coupon';
        $createDto->type = 'fixed';
        $createDto->value = 10.0;
        $createDto->totalQuantity = 100;
        $createDto->startTime = '2024-01-01 00:00:00';
        $createDto->endTime = '2024-12-31 23:59:59';

        $entity = new CouponEntity();
        $entity->create($createDto);

        // Update with invalid startTime (after endTime)
        $updateDto = new CouponDto();
        $updateDto->startTime = '2025-01-01 00:00:00'; // After current endTime

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('优惠券生效时间不合法');
        $entity->update($updateDto);
    }

    public function testUpdateValidatesTimeWindowWhenEndTimeUpdated(): void
    {
        // Requirement 5.1: Validate time window if startTime or endTime are updated
        $createDto = new CouponDto();
        $createDto->name = 'Test Coupon';
        $createDto->type = 'fixed';
        $createDto->value = 10.0;
        $createDto->totalQuantity = 100;
        $createDto->startTime = '2024-01-01 00:00:00';
        $createDto->endTime = '2024-12-31 23:59:59';

        $entity = new CouponEntity();
        $entity->create($createDto);

        // Update with invalid endTime (before startTime)
        $updateDto = new CouponDto();
        $updateDto->endTime = '2023-12-31 23:59:59'; // Before current startTime

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('优惠券生效时间不合法');
        $entity->update($updateDto);
    }

    public function testUpdateValidatesTimeWindowWhenBothTimesUpdated(): void
    {
        // Test validation when both times are updated
        $createDto = new CouponDto();
        $createDto->name = 'Test Coupon';
        $createDto->type = 'fixed';
        $createDto->value = 10.0;
        $createDto->totalQuantity = 100;
        $createDto->startTime = '2024-01-01 00:00:00';
        $createDto->endTime = '2024-12-31 23:59:59';

        $entity = new CouponEntity();
        $entity->create($createDto);

        // Update with invalid time window
        $updateDto = new CouponDto();
        $updateDto->startTime = '2024-12-31 23:59:59';
        $updateDto->endTime = '2024-01-01 00:00:00'; // Invalid: end before start

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('优惠券生效时间不合法');
        $entity->update($updateDto);
    }

    public function testUpdateWithValidTimeWindow(): void
    {
        // Test successful update with valid time window
        $createDto = new CouponDto();
        $createDto->name = 'Test Coupon';
        $createDto->type = 'fixed';
        $createDto->value = 10.0;
        $createDto->totalQuantity = 100;
        $createDto->startTime = '2024-01-01 00:00:00';
        $createDto->endTime = '2024-12-31 23:59:59';

        $entity = new CouponEntity();
        $entity->create($createDto);

        // Update with valid time window
        $updateDto = new CouponDto();
        $updateDto->startTime = '2024-02-01 00:00:00';
        $updateDto->endTime = '2024-11-30 23:59:59';

        $entity->update($updateDto);

        self::assertSame('2024-02-01 00:00:00', $entity->getStartTime());
        self::assertSame('2024-11-30 23:59:59', $entity->getEndTime());
    }

    public function testUpdateWithAllProperties(): void
    {
        // Test updating all properties at once
        $createDto = new CouponDto();
        $createDto->name = 'Original Name';
        $createDto->type = 'fixed';
        $createDto->value = 10.0;
        $createDto->minAmount = 50.0;
        $createDto->totalQuantity = 100;
        $createDto->perUserLimit = 5;
        $createDto->startTime = '2024-01-01 00:00:00';
        $createDto->endTime = '2024-12-31 23:59:59';
        $createDto->status = 'active';
        $createDto->description = 'Original description';

        $entity = new CouponEntity();
        $entity->create($createDto);

        // Update all properties
        $updateDto = new CouponDto();
        $updateDto->name = 'Updated Name';
        $updateDto->type = 'percent';
        $updateDto->value = 20.0;
        $updateDto->minAmount = 100.0;
        $updateDto->totalQuantity = 200;
        $updateDto->perUserLimit = 10;
        $updateDto->startTime = '2024-02-01 00:00:00';
        $updateDto->endTime = '2024-11-30 23:59:59';
        $updateDto->status = 'inactive';
        $updateDto->description = 'Updated description';

        $entity->update($updateDto);

        self::assertSame('Updated Name', $entity->getName());
        self::assertSame('percent', $entity->getType());
        self::assertSame(20.0, $entity->getValue());
        self::assertSame(100.0, $entity->getMinAmount());
        self::assertSame(200, $entity->getTotalQuantity());
        self::assertSame(10, $entity->getPerUserLimit());
        self::assertSame('2024-02-01 00:00:00', $entity->getStartTime());
        self::assertSame('2024-11-30 23:59:59', $entity->getEndTime());
        self::assertSame('inactive', $entity->getStatus());
        self::assertSame('Updated description', $entity->getDescription());
    }

    public function testUpdateWithEmptyDto(): void
    {
        // Test that updating with all null properties doesn't change anything
        $createDto = new CouponDto();
        $createDto->name = 'Original Name';
        $createDto->type = 'fixed';
        $createDto->value = 10.0;
        $createDto->minAmount = 50.0;
        $createDto->totalQuantity = 100;
        $createDto->perUserLimit = 5;
        $createDto->startTime = '2024-01-01 00:00:00';
        $createDto->endTime = '2024-12-31 23:59:59';
        $createDto->description = 'Original description';

        $entity = new CouponEntity();
        $entity->create($createDto);

        // Update with empty DTO (all properties null)
        $updateDto = new CouponDto();
        $entity->update($updateDto);

        // Verify nothing changed
        self::assertSame('Original Name', $entity->getName());
        self::assertSame('fixed', $entity->getType());
        self::assertSame(10.0, $entity->getValue());
        self::assertSame(50.0, $entity->getMinAmount());
        self::assertSame(100, $entity->getTotalQuantity());
        self::assertSame(5, $entity->getPerUserLimit());
        self::assertSame('2024-01-01 00:00:00', $entity->getStartTime());
        self::assertSame('2024-12-31 23:59:59', $entity->getEndTime());
        self::assertSame('active', $entity->getStatus());
        self::assertSame('Original description', $entity->getDescription());
    }

    public function testUpdateAllowsMethodChaining(): void
    {
        // Test that method chaining works with update
        $createDto = new CouponDto();
        $createDto->name = 'Test Coupon';
        $createDto->type = 'fixed';
        $createDto->value = 10.0;
        $createDto->totalQuantity = 100;
        $createDto->startTime = '2024-01-01 00:00:00';
        $createDto->endTime = '2024-12-31 23:59:59';

        $entity = new CouponEntity();
        $entity->create($createDto);

        $updateDto = new CouponDto();
        $updateDto->name = 'Updated Name';

        $result = $entity->update($updateDto)->activate();

        self::assertInstanceOf(CouponEntity::class, $result);
        self::assertSame('Updated Name', $entity->getName());
        self::assertSame('active', $entity->getStatus());
    }

    public function testUpdateDoesNotChangeUsedQuantity(): void
    {
        // Verify that update doesn't modify usedQuantity (it's not in the DTO)
        $createDto = new CouponDto();
        $createDto->name = 'Test Coupon';
        $createDto->type = 'fixed';
        $createDto->value = 10.0;
        $createDto->totalQuantity = 100;
        $createDto->startTime = '2024-01-01 00:00:00';
        $createDto->endTime = '2024-12-31 23:59:59';

        $entity = new CouponEntity();
        $entity->create($createDto);

        // Manually set usedQuantity to simulate usage
        $entity->setUsedQuantity(25);

        // Update other properties
        $updateDto = new CouponDto();
        $updateDto->name = 'Updated Name';
        $updateDto->totalQuantity = 200;

        $entity->update($updateDto);

        // Verify usedQuantity wasn't changed
        self::assertSame(25, $entity->getUsedQuantity());
        self::assertSame('Updated Name', $entity->getName());
        self::assertSame(200, $entity->getTotalQuantity());
    }

    public function testUpdateOnlyStartTimeWithValidWindow(): void
    {
        // Test updating only startTime while keeping endTime
        $createDto = new CouponDto();
        $createDto->name = 'Test Coupon';
        $createDto->type = 'fixed';
        $createDto->value = 10.0;
        $createDto->totalQuantity = 100;
        $createDto->startTime = '2024-01-01 00:00:00';
        $createDto->endTime = '2024-12-31 23:59:59';

        $entity = new CouponEntity();
        $entity->create($createDto);

        // Update only startTime
        $updateDto = new CouponDto();
        $updateDto->startTime = '2024-02-01 00:00:00';

        $entity->update($updateDto);

        self::assertSame('2024-02-01 00:00:00', $entity->getStartTime());
        self::assertSame('2024-12-31 23:59:59', $entity->getEndTime());
    }

    public function testUpdateOnlyEndTimeWithValidWindow(): void
    {
        // Test updating only endTime while keeping startTime
        $createDto = new CouponDto();
        $createDto->name = 'Test Coupon';
        $createDto->type = 'fixed';
        $createDto->value = 10.0;
        $createDto->totalQuantity = 100;
        $createDto->startTime = '2024-01-01 00:00:00';
        $createDto->endTime = '2024-12-31 23:59:59';

        $entity = new CouponEntity();
        $entity->create($createDto);

        // Update only endTime
        $updateDto = new CouponDto();
        $updateDto->endTime = '2024-11-30 23:59:59';

        $entity->update($updateDto);

        self::assertSame('2024-01-01 00:00:00', $entity->getStartTime());
        self::assertSame('2024-11-30 23:59:59', $entity->getEndTime());
    }

    public function testUpdateWithoutTimeChangesDoesNotValidate(): void
    {
        // Test that validation is only triggered when times are updated
        $createDto = new CouponDto();
        $createDto->name = 'Test Coupon';
        $createDto->type = 'fixed';
        $createDto->value = 10.0;
        $createDto->totalQuantity = 100;
        $createDto->startTime = '2024-01-01 00:00:00';
        $createDto->endTime = '2024-12-31 23:59:59';

        $entity = new CouponEntity();
        $entity->create($createDto);

        // Update without changing times - should not trigger validation
        $updateDto = new CouponDto();
        $updateDto->name = 'Updated Name';
        $updateDto->value = 20.0;
        $updateDto->description = 'Updated description';

        // This should succeed without validation
        $entity->update($updateDto);

        self::assertSame('Updated Name', $entity->getName());
        self::assertSame(20.0, $entity->getValue());
        self::assertSame('Updated description', $entity->getDescription());
    }
}
