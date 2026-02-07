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

namespace HyperfTests\Unit\Domain\Coupon\Mapper;

use App\Domain\Marketing\Coupon\Entity\CouponEntity;
use App\Domain\Marketing\Coupon\Mapper\CouponMapper;
use App\Infrastructure\Model\Coupon\Coupon;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Domain\Marketing\Coupon\Mapper\CouponMapper
 */
final class CouponMapperTest extends TestCase
{
    /**
     * Test that getNewEntity() returns a new CouponEntity instance.
     * Validates: Requirements 7.1, 7.2.
     */
    public function testGetNewEntityReturnsNewCouponEntity(): void
    {
        $entity = CouponMapper::getNewEntity();

        self::assertInstanceOf(CouponEntity::class, $entity);
        self::assertSame(0, $entity->getId());
        self::assertSame('active', $entity->getStatus());
        self::assertSame(0, $entity->getUsedQuantity());
    }

    /**
     * Test that fromModel() properly converts all Model properties to Entity.
     * Validates: Requirements 7.3.
     */
    public function testFromModelConvertsAllProperties(): void
    {
        // Create a mock Coupon model with all properties
        $model = new Coupon();
        $model->id = 123;
        $model->name = 'Test Coupon';
        $model->type = 'fixed';
        $model->value = 10.50;
        $model->min_amount = 50.00;
        $model->total_quantity = 100;
        $model->used_quantity = 25;
        $model->per_user_limit = 2;
        $model->start_time = Carbon::parse('2024-01-01 00:00:00');
        $model->end_time = Carbon::parse('2024-12-31 23:59:59');
        $model->status = 'active';
        $model->description = 'Test description';

        $entity = CouponMapper::fromModel($model);

        self::assertInstanceOf(CouponEntity::class, $entity);
        self::assertSame(123, $entity->getId());
        self::assertSame('Test Coupon', $entity->getName());
        self::assertSame('fixed', $entity->getType());
        self::assertSame(10.50, $entity->getValue());
        self::assertSame(50.00, $entity->getMinAmount());
        self::assertSame(100, $entity->getTotalQuantity());
        self::assertSame(25, $entity->getUsedQuantity());
        self::assertSame(2, $entity->getPerUserLimit());
        self::assertSame('2024-01-01 00:00:00', $entity->getStartTime());
        self::assertSame('2024-12-31 23:59:59', $entity->getEndTime());
        self::assertSame('active', $entity->getStatus());
        self::assertSame('Test description', $entity->getDescription());
    }

    /**
     * Test that fromModel() handles null values correctly.
     * Validates: Requirements 7.3.
     */
    public function testFromModelHandlesNullValues(): void
    {
        $model = new Coupon();
        $model->id = 1;
        $model->name = 'Minimal Coupon';
        $model->type = 'percent';
        $model->value = null;
        $model->min_amount = null;
        $model->total_quantity = null;
        $model->used_quantity = null;
        $model->per_user_limit = null;
        $model->start_time = null;
        $model->end_time = null;
        $model->status = 'inactive';
        $model->description = null;

        $entity = CouponMapper::fromModel($model);

        self::assertInstanceOf(CouponEntity::class, $entity);
        self::assertSame(1, $entity->getId());
        self::assertSame('Minimal Coupon', $entity->getName());
        self::assertSame('percent', $entity->getType());
        self::assertNull($entity->getValue());
        self::assertNull($entity->getMinAmount());
        self::assertNull($entity->getTotalQuantity());
        self::assertNull($entity->getUsedQuantity());
        self::assertNull($entity->getPerUserLimit());
        self::assertNull($entity->getStartTime());
        self::assertNull($entity->getEndTime());
        self::assertSame('inactive', $entity->getStatus());
        self::assertNull($entity->getDescription());
    }

    /**
     * Test that toArray() delegates to entity's toArray() method.
     */
    public function testToArrayDelegatesToEntity(): void
    {
        $entity = new CouponEntity();
        $entity->setName('Test Coupon');
        $entity->setType('fixed');
        $entity->setValue(10.00);

        $result = CouponMapper::toArray($entity);

        self::assertIsArray($result);
        self::assertArrayHasKey('name', $result);
        self::assertSame('Test Coupon', $result['name']);
        self::assertArrayHasKey('type', $result);
        self::assertSame('fixed', $result['type']);
        self::assertArrayHasKey('value', $result);
        self::assertSame(10.00, $result['value']);
    }
}
