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

use App\Domain\Coupon\Entity\CouponUserEntity;
use App\Domain\Coupon\Mapper\CouponUserMapper;
use App\Infrastructure\Model\Coupon\CouponUser;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Domain\Coupon\Mapper\CouponUserMapper
 */
final class CouponUserMapperTest extends TestCase
{
    /**
     * Test that getNewEntity() returns a new CouponUserEntity instance.
     * Validates: Requirements 7.4, 7.5.
     */
    public function testGetNewEntityReturnsNewCouponUserEntity(): void
    {
        $entity = CouponUserMapper::getNewEntity();

        self::assertInstanceOf(CouponUserEntity::class, $entity);
        self::assertSame(0, $entity->getId());
        self::assertSame('unused', $entity->getStatus());
    }

    /**
     * Test that fromModel() properly converts all Model properties to Entity.
     * Validates: Requirements 7.6.
     */
    public function testFromModelConvertsAllProperties(): void
    {
        // Create a mock CouponUser model with all properties
        $model = new CouponUser();
        $model->id = 456;
        $model->coupon_id = 123;
        $model->member_id = 789;
        $model->order_id = 999;
        $model->status = 'used';
        $model->received_at = Carbon::parse('2024-01-01 10:00:00');
        $model->used_at = Carbon::parse('2024-01-15 14:30:00');
        $model->expire_at = Carbon::parse('2024-12-31 23:59:59');

        $entity = CouponUserMapper::fromModel($model);

        self::assertInstanceOf(CouponUserEntity::class, $entity);
        self::assertSame(456, $entity->getId());
        self::assertSame(123, $entity->getCouponId());
        self::assertSame(789, $entity->getMemberId());
        self::assertSame(999, $entity->getOrderId());
        self::assertSame('used', $entity->getStatus());
        self::assertSame('2024-01-01 10:00:00', $entity->getReceivedAt());
        self::assertSame('2024-01-15 14:30:00', $entity->getUsedAt());
        self::assertSame('2024-12-31 23:59:59', $entity->getExpireAt());
    }

    /**
     * Test that fromModel() handles null values correctly.
     * Validates: Requirements 7.6.
     */
    public function testFromModelHandlesNullValues(): void
    {
        $model = new CouponUser();
        $model->id = 1;
        $model->coupon_id = null;
        $model->member_id = null;
        $model->order_id = null;
        $model->status = 'unused';
        $model->received_at = null;
        $model->used_at = null;
        $model->expire_at = null;

        $entity = CouponUserMapper::fromModel($model);

        self::assertInstanceOf(CouponUserEntity::class, $entity);
        self::assertSame(1, $entity->getId());
        self::assertNull($entity->getCouponId());
        self::assertNull($entity->getMemberId());
        self::assertNull($entity->getOrderId());
        self::assertSame('unused', $entity->getStatus());
        self::assertNull($entity->getReceivedAt());
        self::assertNull($entity->getUsedAt());
        self::assertNull($entity->getExpireAt());
    }

    /**
     * Test that fromModel() handles partial null values correctly.
     * Validates: Requirements 7.6.
     */
    public function testFromModelHandlesPartialNullValues(): void
    {
        $model = new CouponUser();
        $model->id = 2;
        $model->coupon_id = 100;
        $model->member_id = 200;
        $model->order_id = null;
        $model->status = 'unused';
        $model->received_at = Carbon::parse('2024-02-01 08:00:00');
        $model->used_at = null;
        $model->expire_at = Carbon::parse('2024-03-01 23:59:59');

        $entity = CouponUserMapper::fromModel($model);

        self::assertInstanceOf(CouponUserEntity::class, $entity);
        self::assertSame(2, $entity->getId());
        self::assertSame(100, $entity->getCouponId());
        self::assertSame(200, $entity->getMemberId());
        self::assertNull($entity->getOrderId());
        self::assertSame('unused', $entity->getStatus());
        self::assertSame('2024-02-01 08:00:00', $entity->getReceivedAt());
        self::assertNull($entity->getUsedAt());
        self::assertSame('2024-03-01 23:59:59', $entity->getExpireAt());
    }
}
