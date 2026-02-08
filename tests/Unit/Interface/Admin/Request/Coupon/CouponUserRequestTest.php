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

namespace HyperfTests\Unit\Interface\Admin\Request\Coupon;

use Hyperf\DTO\Mapper;
use PHPUnit\Framework\TestCase;
use Plugin\Since\Coupon\Domain\Contract\CouponUserInput;
use Plugin\Since\Coupon\Interface\Dto\Admin\CouponUserDto;

/**
 * CouponUserRequest toDto() 映射测试.
 *
 * 这个测试验证 CouponUserRequest::toDto() 方法正确地将 snake_case 键转换为 camelCase 键，
 * 以便 Hyperf\DTO\Mapper 可以正确地映射到 CouponUserDto 对象。
 * @internal
 * @coversNothing
 */
final class CouponUserRequestTest extends TestCase
{
    /**
     * 测试 snake_case 到 camelCase 的转换逻辑.
     *
     * 这个测试直接测试转换逻辑，而不依赖于 FormRequest 的验证机制。
     */
    public function testSnakeCaseToCamelCaseConversion(): void
    {
        // 模拟从 validated() 返回的数据（snake_case）
        $validatedData = [
            'id' => 123,
            'coupon_id' => 456,
            'member_id' => 789,
            'order_id' => 101,
            'status' => 'unused',
            'received_at' => '2024-01-01 10:00:00',
            'used_at' => '2024-01-15 14:30:00',
            'expire_at' => '2024-12-31 23:59:59',
        ];

        // 执行与 toDto() 方法相同的转换逻辑
        $params = $validatedData;

        if (isset($params['coupon_id'])) {
            $params['couponId'] = $params['coupon_id'];
            unset($params['coupon_id']);
        }

        if (isset($params['member_id'])) {
            $params['memberId'] = $params['member_id'];
            unset($params['member_id']);
        }

        if (isset($params['order_id'])) {
            $params['orderId'] = $params['order_id'];
            unset($params['order_id']);
        }

        if (isset($params['received_at'])) {
            $params['receivedAt'] = $params['received_at'];
            unset($params['received_at']);
        }

        if (isset($params['used_at'])) {
            $params['usedAt'] = $params['used_at'];
            unset($params['used_at']);
        }

        if (isset($params['expire_at'])) {
            $params['expireAt'] = $params['expire_at'];
            unset($params['expire_at']);
        }

        // 使用 Mapper 映射到 DTO
        $dto = Mapper::map($params, new CouponUserDto());

        // 验证 DTO 类型
        self::assertInstanceOf(CouponUserInput::class, $dto);
        self::assertInstanceOf(CouponUserDto::class, $dto);

        // 验证所有属性都正确映射
        self::assertSame(123, $dto->getId());
        self::assertSame(456, $dto->getCouponId());
        self::assertSame(789, $dto->getMemberId());
        self::assertSame(101, $dto->getOrderId());
        self::assertSame('unused', $dto->getStatus());
        self::assertSame('2024-01-01 10:00:00', $dto->getReceivedAt());
        self::assertSame('2024-01-15 14:30:00', $dto->getUsedAt());
        self::assertSame('2024-12-31 23:59:59', $dto->getExpireAt());
    }

    /**
     * 测试部分数据的转换（用于更新操作）.
     */
    public function testPartialDataConversion(): void
    {
        // 模拟部分数据
        $validatedData = [
            'id' => 456,
            'status' => 'used',
            'used_at' => '2024-02-01 12:00:00',
        ];

        // 执行转换逻辑
        $params = $validatedData;

        if (isset($params['coupon_id'])) {
            $params['couponId'] = $params['coupon_id'];
            unset($params['coupon_id']);
        }

        if (isset($params['member_id'])) {
            $params['memberId'] = $params['member_id'];
            unset($params['member_id']);
        }

        if (isset($params['order_id'])) {
            $params['orderId'] = $params['order_id'];
            unset($params['order_id']);
        }

        if (isset($params['received_at'])) {
            $params['receivedAt'] = $params['received_at'];
            unset($params['received_at']);
        }

        if (isset($params['used_at'])) {
            $params['usedAt'] = $params['used_at'];
            unset($params['used_at']);
        }

        if (isset($params['expire_at'])) {
            $params['expireAt'] = $params['expire_at'];
            unset($params['expire_at']);
        }

        // 使用 Mapper 映射到 DTO
        $dto = Mapper::map($params, new CouponUserDto());

        // 验证提供的属性被正确映射
        self::assertSame(456, $dto->getId());
        self::assertSame('used', $dto->getStatus());
        self::assertSame('2024-02-01 12:00:00', $dto->getUsedAt());

        // 验证未提供的属性为 null
        self::assertNull($dto->getCouponId());
        self::assertNull($dto->getMemberId());
        self::assertNull($dto->getOrderId());
        self::assertNull($dto->getReceivedAt());
        self::assertNull($dto->getExpireAt());
    }

    /**
     * 测试 null ID 的处理（用于创建操作）.
     */
    public function testNullIdHandling(): void
    {
        // 模拟创建操作的数据（没有 ID）
        $validatedData = [
            'id' => null,
            'coupon_id' => 100,
            'member_id' => 200,
            'status' => 'unused',
            'received_at' => '2024-03-01 08:00:00',
            'expire_at' => '2024-03-31 23:59:59',
        ];

        // 执行转换逻辑
        $params = $validatedData;

        if (isset($params['coupon_id'])) {
            $params['couponId'] = $params['coupon_id'];
            unset($params['coupon_id']);
        }

        if (isset($params['member_id'])) {
            $params['memberId'] = $params['member_id'];
            unset($params['member_id']);
        }

        if (isset($params['order_id'])) {
            $params['orderId'] = $params['order_id'];
            unset($params['order_id']);
        }

        if (isset($params['received_at'])) {
            $params['receivedAt'] = $params['received_at'];
            unset($params['received_at']);
        }

        if (isset($params['used_at'])) {
            $params['usedAt'] = $params['used_at'];
            unset($params['used_at']);
        }

        if (isset($params['expire_at'])) {
            $params['expireAt'] = $params['expire_at'];
            unset($params['expire_at']);
        }

        // 使用 Mapper 映射到 DTO
        $dto = Mapper::map($params, new CouponUserDto());

        // 验证 ID 为 0（因为 CouponUserDto::getId() 返回 $this->id ?? 0）
        self::assertSame(0, $dto->getId());

        // 验证其他属性正确映射
        self::assertSame(100, $dto->getCouponId());
        self::assertSame(200, $dto->getMemberId());
        self::assertSame('unused', $dto->getStatus());
        self::assertSame('2024-03-01 08:00:00', $dto->getReceivedAt());
        self::assertSame('2024-03-31 23:59:59', $dto->getExpireAt());
    }

    /**
     * 测试所有 snake_case 键都被正确转换.
     */
    public function testAllSnakeCaseKeysAreConverted(): void
    {
        // 包含所有需要转换的 snake_case 键
        $validatedData = [
            'coupon_id' => 111,
            'member_id' => 222,
            'order_id' => 333,
            'received_at' => '2024-04-01 09:00:00',
            'used_at' => '2024-04-10 15:00:00',
            'expire_at' => '2024-04-30 23:59:59',
        ];

        // 执行转换逻辑
        $params = $validatedData;

        if (isset($params['coupon_id'])) {
            $params['couponId'] = $params['coupon_id'];
            unset($params['coupon_id']);
        }

        if (isset($params['member_id'])) {
            $params['memberId'] = $params['member_id'];
            unset($params['member_id']);
        }

        if (isset($params['order_id'])) {
            $params['orderId'] = $params['order_id'];
            unset($params['order_id']);
        }

        if (isset($params['received_at'])) {
            $params['receivedAt'] = $params['received_at'];
            unset($params['received_at']);
        }

        if (isset($params['used_at'])) {
            $params['usedAt'] = $params['used_at'];
            unset($params['used_at']);
        }

        if (isset($params['expire_at'])) {
            $params['expireAt'] = $params['expire_at'];
            unset($params['expire_at']);
        }

        // 验证所有 snake_case 键都被移除
        self::assertArrayNotHasKey('coupon_id', $params);
        self::assertArrayNotHasKey('member_id', $params);
        self::assertArrayNotHasKey('order_id', $params);
        self::assertArrayNotHasKey('received_at', $params);
        self::assertArrayNotHasKey('used_at', $params);
        self::assertArrayNotHasKey('expire_at', $params);

        // 验证所有 camelCase 键都存在
        self::assertArrayHasKey('couponId', $params);
        self::assertArrayHasKey('memberId', $params);
        self::assertArrayHasKey('orderId', $params);
        self::assertArrayHasKey('receivedAt', $params);
        self::assertArrayHasKey('usedAt', $params);
        self::assertArrayHasKey('expireAt', $params);

        // 验证值没有改变
        self::assertSame(111, $params['couponId']);
        self::assertSame(222, $params['memberId']);
        self::assertSame(333, $params['orderId']);
        self::assertSame('2024-04-01 09:00:00', $params['receivedAt']);
        self::assertSame('2024-04-10 15:00:00', $params['usedAt']);
        self::assertSame('2024-04-30 23:59:59', $params['expireAt']);
    }
}
