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

namespace HyperfTests\Unit\Interface\Admin\Dto\Coupon;

use PHPUnit\Framework\TestCase;
use Plugin\Since\Coupon\Domain\Contract\CouponUserInput;
use Plugin\Since\Coupon\Interface\Dto\Admin\CouponUserDto;

/**
 * 用户优惠券DTO测试.
 * @internal
 * @coversNothing
 */
final class CouponUserDtoTest extends TestCase
{
    public function testImplementsCouponUserInput(): void
    {
        $dto = new CouponUserDto();
        self::assertInstanceOf(CouponUserInput::class, $dto);
    }

    public function testGetIdReturnsZeroWhenNull(): void
    {
        $dto = new CouponUserDto();
        self::assertSame(0, $dto->getId());
    }

    public function testGetIdReturnsSetValue(): void
    {
        $dto = new CouponUserDto();
        $dto->id = 123;
        self::assertSame(123, $dto->getId());
    }

    public function testGetCouponIdReturnsNull(): void
    {
        $dto = new CouponUserDto();
        self::assertNull($dto->getCouponId());
    }

    public function testGetCouponIdReturnsSetValue(): void
    {
        $dto = new CouponUserDto();
        $dto->couponId = 456;
        self::assertSame(456, $dto->getCouponId());
    }

    public function testGetMemberIdReturnsNull(): void
    {
        $dto = new CouponUserDto();
        self::assertNull($dto->getMemberId());
    }

    public function testGetMemberIdReturnsSetValue(): void
    {
        $dto = new CouponUserDto();
        $dto->memberId = 789;
        self::assertSame(789, $dto->getMemberId());
    }

    public function testGetOrderIdReturnsNull(): void
    {
        $dto = new CouponUserDto();
        self::assertNull($dto->getOrderId());
    }

    public function testGetOrderIdReturnsSetValue(): void
    {
        $dto = new CouponUserDto();
        $dto->orderId = 101;
        self::assertSame(101, $dto->getOrderId());
    }

    public function testGetStatusReturnsNull(): void
    {
        $dto = new CouponUserDto();
        self::assertNull($dto->getStatus());
    }

    public function testGetStatusReturnsSetValue(): void
    {
        $dto = new CouponUserDto();
        $dto->status = 'unused';
        self::assertSame('unused', $dto->getStatus());
    }

    public function testGetReceivedAtReturnsNull(): void
    {
        $dto = new CouponUserDto();
        self::assertNull($dto->getReceivedAt());
    }

    public function testGetReceivedAtReturnsSetValue(): void
    {
        $dto = new CouponUserDto();
        $dto->receivedAt = '2024-01-01 10:00:00';
        self::assertSame('2024-01-01 10:00:00', $dto->getReceivedAt());
    }

    public function testGetUsedAtReturnsNull(): void
    {
        $dto = new CouponUserDto();
        self::assertNull($dto->getUsedAt());
    }

    public function testGetUsedAtReturnsSetValue(): void
    {
        $dto = new CouponUserDto();
        $dto->usedAt = '2024-01-02 10:00:00';
        self::assertSame('2024-01-02 10:00:00', $dto->getUsedAt());
    }

    public function testGetExpireAtReturnsNull(): void
    {
        $dto = new CouponUserDto();
        self::assertNull($dto->getExpireAt());
    }

    public function testGetExpireAtReturnsSetValue(): void
    {
        $dto = new CouponUserDto();
        $dto->expireAt = '2024-12-31 23:59:59';
        self::assertSame('2024-12-31 23:59:59', $dto->getExpireAt());
    }

    public function testAllPropertiesCanBeSet(): void
    {
        $dto = new CouponUserDto();
        $dto->id = 1;
        $dto->couponId = 2;
        $dto->memberId = 3;
        $dto->orderId = 4;
        $dto->status = 'used';
        $dto->receivedAt = '2024-01-01 10:00:00';
        $dto->usedAt = '2024-01-02 10:00:00';
        $dto->expireAt = '2024-12-31 23:59:59';

        self::assertSame(1, $dto->getId());
        self::assertSame(2, $dto->getCouponId());
        self::assertSame(3, $dto->getMemberId());
        self::assertSame(4, $dto->getOrderId());
        self::assertSame('used', $dto->getStatus());
        self::assertSame('2024-01-01 10:00:00', $dto->getReceivedAt());
        self::assertSame('2024-01-02 10:00:00', $dto->getUsedAt());
        self::assertSame('2024-12-31 23:59:59', $dto->getExpireAt());
    }
}
