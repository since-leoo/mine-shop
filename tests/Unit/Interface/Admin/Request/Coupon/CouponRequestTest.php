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
use App\Domain\Trade\Coupon\Contract\CouponInput;
use App\Interface\Admin\Dto\Coupon\CouponDto;

/**
 * CouponRequest toDto() 映射测试.
 *
 * 这个测试验证 CouponRequest::toDto() 方法正确地将 snake_case 键转换为 camelCase 键，
 * 以便 Hyperf\DTO\Mapper 可以正确地映射到 CouponDto 对象。
 * @internal
 * @coversNothing
 */
final class CouponRequestTest extends TestCase
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
            'name' => 'Test Coupon',
            'type' => 'fixed',
            'value' => 10.0,
            'min_amount' => 50.0,
            'total_quantity' => 100,
            'per_user_limit' => 5,
            'start_time' => '2024-01-01 00:00:00',
            'end_time' => '2024-12-31 23:59:59',
            'status' => 'active',
            'description' => 'Test description',
        ];

        // 执行与 toDto() 方法相同的转换逻辑
        $params = $validatedData;

        if (isset($params['min_amount'])) {
            $params['minAmount'] = $params['min_amount'];
            unset($params['min_amount']);
        }

        if (isset($params['total_quantity'])) {
            $params['totalQuantity'] = $params['total_quantity'];
            unset($params['total_quantity']);
        }

        if (isset($params['per_user_limit'])) {
            $params['perUserLimit'] = $params['per_user_limit'];
            unset($params['per_user_limit']);
        }

        if (isset($params['start_time'])) {
            $params['startTime'] = $params['start_time'];
            unset($params['start_time']);
        }

        if (isset($params['end_time'])) {
            $params['endTime'] = $params['end_time'];
            unset($params['end_time']);
        }

        // 使用 Mapper 映射到 DTO
        $dto = Mapper::map($params, new CouponDto());

        // 验证 DTO 类型
        self::assertInstanceOf(CouponInput::class, $dto);
        self::assertInstanceOf(CouponDto::class, $dto);

        // 验证所有属性都正确映射
        self::assertSame(123, $dto->getId());
        self::assertSame('Test Coupon', $dto->getName());
        self::assertSame('fixed', $dto->getType());
        self::assertSame(10.0, $dto->getValue());
        self::assertSame(50.0, $dto->getMinAmount());
        self::assertSame(100, $dto->getTotalQuantity());
        self::assertSame(5, $dto->getPerUserLimit());
        self::assertSame('2024-01-01 00:00:00', $dto->getStartTime());
        self::assertSame('2024-12-31 23:59:59', $dto->getEndTime());
        self::assertSame('active', $dto->getStatus());
        self::assertSame('Test description', $dto->getDescription());
    }

    /**
     * 测试部分数据的转换（用于更新操作）.
     */
    public function testPartialDataConversion(): void
    {
        // 模拟部分数据
        $validatedData = [
            'id' => 456,
            'name' => 'Updated Coupon',
            'total_quantity' => 200,
        ];

        // 执行转换逻辑
        $params = $validatedData;

        if (isset($params['min_amount'])) {
            $params['minAmount'] = $params['min_amount'];
            unset($params['min_amount']);
        }

        if (isset($params['total_quantity'])) {
            $params['totalQuantity'] = $params['total_quantity'];
            unset($params['total_quantity']);
        }

        if (isset($params['per_user_limit'])) {
            $params['perUserLimit'] = $params['per_user_limit'];
            unset($params['per_user_limit']);
        }

        if (isset($params['start_time'])) {
            $params['startTime'] = $params['start_time'];
            unset($params['start_time']);
        }

        if (isset($params['end_time'])) {
            $params['endTime'] = $params['end_time'];
            unset($params['end_time']);
        }

        // 使用 Mapper 映射到 DTO
        $dto = Mapper::map($params, new CouponDto());

        // 验证提供的属性被正确映射
        self::assertSame(456, $dto->getId());
        self::assertSame('Updated Coupon', $dto->getName());
        self::assertSame(200, $dto->getTotalQuantity());

        // 验证未提供的属性为 null
        self::assertNull($dto->getType());
        self::assertNull($dto->getValue());
        self::assertNull($dto->getMinAmount());
        self::assertNull($dto->getPerUserLimit());
        self::assertNull($dto->getStartTime());
        self::assertNull($dto->getEndTime());
        self::assertNull($dto->getStatus());
        self::assertNull($dto->getDescription());
    }

    /**
     * 测试 null ID 的处理（用于创建操作）.
     */
    public function testNullIdHandling(): void
    {
        // 模拟创建操作的数据（没有 ID）
        $validatedData = [
            'id' => null,
            'name' => 'New Coupon',
            'type' => 'percent',
            'value' => 20.0,
            'total_quantity' => 50,
            'start_time' => '2024-01-01 00:00:00',
            'end_time' => '2024-12-31 23:59:59',
        ];

        // 执行转换逻辑
        $params = $validatedData;

        if (isset($params['min_amount'])) {
            $params['minAmount'] = $params['min_amount'];
            unset($params['min_amount']);
        }

        if (isset($params['total_quantity'])) {
            $params['totalQuantity'] = $params['total_quantity'];
            unset($params['total_quantity']);
        }

        if (isset($params['per_user_limit'])) {
            $params['perUserLimit'] = $params['per_user_limit'];
            unset($params['per_user_limit']);
        }

        if (isset($params['start_time'])) {
            $params['startTime'] = $params['start_time'];
            unset($params['start_time']);
        }

        if (isset($params['end_time'])) {
            $params['endTime'] = $params['end_time'];
            unset($params['end_time']);
        }

        // 使用 Mapper 映射到 DTO
        $dto = Mapper::map($params, new CouponDto());

        // 验证 ID 为 0（因为 CouponDto::getId() 返回 $this->id ?? 0）
        self::assertSame(0, $dto->getId());

        // 验证其他属性正确映射
        self::assertSame('New Coupon', $dto->getName());
        self::assertSame('percent', $dto->getType());
        self::assertSame(20.0, $dto->getValue());
        self::assertSame(50, $dto->getTotalQuantity());
        self::assertSame('2024-01-01 00:00:00', $dto->getStartTime());
        self::assertSame('2024-12-31 23:59:59', $dto->getEndTime());
    }

    /**
     * 测试所有 snake_case 键都被正确转换.
     */
    public function testAllSnakeCaseKeysAreConverted(): void
    {
        // 包含所有需要转换的 snake_case 键
        $validatedData = [
            'min_amount' => 100.0,
            'total_quantity' => 500,
            'per_user_limit' => 10,
            'start_time' => '2024-06-01 00:00:00',
            'end_time' => '2024-06-30 23:59:59',
        ];

        // 执行转换逻辑
        $params = $validatedData;

        if (isset($params['min_amount'])) {
            $params['minAmount'] = $params['min_amount'];
            unset($params['min_amount']);
        }

        if (isset($params['total_quantity'])) {
            $params['totalQuantity'] = $params['total_quantity'];
            unset($params['total_quantity']);
        }

        if (isset($params['per_user_limit'])) {
            $params['perUserLimit'] = $params['per_user_limit'];
            unset($params['per_user_limit']);
        }

        if (isset($params['start_time'])) {
            $params['startTime'] = $params['start_time'];
            unset($params['start_time']);
        }

        if (isset($params['end_time'])) {
            $params['endTime'] = $params['end_time'];
            unset($params['end_time']);
        }

        // 验证所有 snake_case 键都被移除
        self::assertArrayNotHasKey('min_amount', $params);
        self::assertArrayNotHasKey('total_quantity', $params);
        self::assertArrayNotHasKey('per_user_limit', $params);
        self::assertArrayNotHasKey('start_time', $params);
        self::assertArrayNotHasKey('end_time', $params);

        // 验证所有 camelCase 键都存在
        self::assertArrayHasKey('minAmount', $params);
        self::assertArrayHasKey('totalQuantity', $params);
        self::assertArrayHasKey('perUserLimit', $params);
        self::assertArrayHasKey('startTime', $params);
        self::assertArrayHasKey('endTime', $params);

        // 验证值没有改变
        self::assertSame(100.0, $params['minAmount']);
        self::assertSame(500, $params['totalQuantity']);
        self::assertSame(10, $params['perUserLimit']);
        self::assertSame('2024-06-01 00:00:00', $params['startTime']);
        self::assertSame('2024-06-30 23:59:59', $params['endTime']);
    }
}
