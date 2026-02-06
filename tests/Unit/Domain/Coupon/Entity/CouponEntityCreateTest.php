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

use App\Domain\Coupon\Entity\CouponEntity;
use App\Interface\Admin\DTO\Coupon\CouponDto;
use PHPUnit\Framework\TestCase;

/**
 * CouponEntity create() method test.
 * Tests requirements 4.1, 4.2, 4.3, 4.4, 4.5.
 * @internal
 * @coversNothing
 */
final class CouponEntityCreateTest extends TestCase
{
    public function testCreateInitializesAllPropertiesFromDto(): void
    {
        // Requirement 4.2: Initialize all properties from DTO
        $dto = new CouponDto();
        $dto->name = 'Test Coupon';
        $dto->type = 'fixed';
        $dto->value = 10.0;
        $dto->minAmount = 50.0;
        $dto->totalQuantity = 100;
        $dto->perUserLimit = 5;
        $dto->startTime = '2024-01-01 00:00:00';
        $dto->endTime = '2024-12-31 23:59:59';
        $dto->description = 'Test description';

        $entity = new CouponEntity();
        $entity->create($dto);

        self::assertSame('Test Coupon', $entity->getName());
        self::assertSame('fixed', $entity->getType());
        self::assertSame(10.0, $entity->getValue());
        self::assertSame(50.0, $entity->getMinAmount());
        self::assertSame(100, $entity->getTotalQuantity());
        self::assertSame(5, $entity->getPerUserLimit());
        self::assertSame('2024-01-01 00:00:00', $entity->getStartTime());
        self::assertSame('2024-12-31 23:59:59', $entity->getEndTime());
        self::assertSame('Test description', $entity->getDescription());
    }

    public function testCreateSetsUsedQuantityToZero(): void
    {
        // Requirement 4.4: Set usedQuantity=0
        $dto = new CouponDto();
        $dto->name = 'Test Coupon';
        $dto->type = 'fixed';
        $dto->value = 10.0;
        $dto->totalQuantity = 100;
        $dto->startTime = '2024-01-01 00:00:00';
        $dto->endTime = '2024-12-31 23:59:59';

        $entity = new CouponEntity();
        $entity->create($dto);

        self::assertSame(0, $entity->getUsedQuantity());
    }

    public function testCreateSetsStatusToActive(): void
    {
        // Requirement 4.3: Set status='active'
        $dto = new CouponDto();
        $dto->name = 'Test Coupon';
        $dto->type = 'fixed';
        $dto->value = 10.0;
        $dto->totalQuantity = 100;
        $dto->startTime = '2024-01-01 00:00:00';
        $dto->endTime = '2024-12-31 23:59:59';

        $entity = new CouponEntity();
        $entity->create($dto);

        self::assertSame('active', $entity->getStatus());
    }

    public function testCreateReturnsSelfForMethodChaining(): void
    {
        // Requirement 4.5: Return $this for method chaining
        $dto = new CouponDto();
        $dto->name = 'Test Coupon';
        $dto->type = 'fixed';
        $dto->value = 10.0;
        $dto->totalQuantity = 100;
        $dto->startTime = '2024-01-01 00:00:00';
        $dto->endTime = '2024-12-31 23:59:59';

        $entity = new CouponEntity();
        $result = $entity->create($dto);

        self::assertSame($entity, $result);
    }

    public function testCreateValidatesTimeWindow(): void
    {
        // Requirement 4.1: Call ensureTimeWindowIsValid()
        $dto = new CouponDto();
        $dto->name = 'Test Coupon';
        $dto->type = 'fixed';
        $dto->value = 10.0;
        $dto->totalQuantity = 100;
        $dto->startTime = '2024-12-31 23:59:59';
        $dto->endTime = '2024-01-01 00:00:00'; // Invalid: end before start

        $entity = new CouponEntity();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('优惠券生效时间不合法');
        $entity->create($dto);
    }

    public function testCreateWithMinimalRequiredFields(): void
    {
        // Test with only required fields
        $dto = new CouponDto();
        $dto->name = 'Minimal Coupon';
        $dto->type = 'percent';
        $dto->value = 15.0;
        $dto->totalQuantity = 50;
        $dto->startTime = '2024-01-01 00:00:00';
        $dto->endTime = '2024-12-31 23:59:59';

        $entity = new CouponEntity();
        $entity->create($dto);

        self::assertSame('Minimal Coupon', $entity->getName());
        self::assertSame('percent', $entity->getType());
        self::assertSame(15.0, $entity->getValue());
        self::assertSame(50, $entity->getTotalQuantity());
        self::assertNull($entity->getMinAmount());
        self::assertNull($entity->getPerUserLimit());
        self::assertNull($entity->getDescription());
        self::assertSame(0, $entity->getUsedQuantity());
        self::assertSame('active', $entity->getStatus());
    }

    public function testCreateWithNullOptionalFields(): void
    {
        // Test that null optional fields are handled correctly
        $dto = new CouponDto();
        $dto->name = 'Test Coupon';
        $dto->type = 'fixed';
        $dto->value = 10.0;
        $dto->totalQuantity = 100;
        $dto->minAmount = null;
        $dto->perUserLimit = null;
        $dto->description = null;
        $dto->startTime = '2024-01-01 00:00:00';
        $dto->endTime = '2024-12-31 23:59:59';

        $entity = new CouponEntity();
        $entity->create($dto);

        self::assertNull($entity->getMinAmount());
        self::assertNull($entity->getPerUserLimit());
        self::assertNull($entity->getDescription());
    }

    public function testCreateAllowsMethodChaining(): void
    {
        // Test that method chaining works
        $dto = new CouponDto();
        $dto->name = 'Chainable Coupon';
        $dto->type = 'fixed';
        $dto->value = 20.0;
        $dto->totalQuantity = 200;
        $dto->startTime = '2024-01-01 00:00:00';
        $dto->endTime = '2024-12-31 23:59:59';

        $entity = new CouponEntity();
        $result = $entity->create($dto)->activate();

        self::assertInstanceOf(CouponEntity::class, $result);
        self::assertSame('active', $entity->getStatus());
    }

    public function testCreateWithValidTimeWindow(): void
    {
        // Test with valid time window (start < end)
        $dto = new CouponDto();
        $dto->name = 'Valid Time Coupon';
        $dto->type = 'fixed';
        $dto->value = 10.0;
        $dto->totalQuantity = 100;
        $dto->startTime = '2024-01-01 00:00:00';
        $dto->endTime = '2024-01-01 00:00:01'; // Just 1 second difference

        $entity = new CouponEntity();
        $entity->create($dto);

        self::assertSame('2024-01-01 00:00:00', $entity->getStartTime());
        self::assertSame('2024-01-01 00:00:01', $entity->getEndTime());
    }

    public function testCreateWithSameStartAndEndTimeThrowsException(): void
    {
        // Test that same start and end time is invalid
        $dto = new CouponDto();
        $dto->name = 'Invalid Time Coupon';
        $dto->type = 'fixed';
        $dto->value = 10.0;
        $dto->totalQuantity = 100;
        $dto->startTime = '2024-01-01 00:00:00';
        $dto->endTime = '2024-01-01 00:00:00'; // Same time

        $entity = new CouponEntity();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('优惠券生效时间不合法');
        $entity->create($dto);
    }
}
