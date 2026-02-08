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

namespace HyperfTests\Unit\Infrastructure\Model;

use App\Infrastructure\Model\GroupBuy\GroupBuy;
use App\Infrastructure\Model\GroupBuy\GroupBuyOrder;
use App\Infrastructure\Model\Member\Member;
use App\Infrastructure\Model\Order\Order;
use Hyperf\Database\Model\Relations\BelongsTo;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for GroupBuyOrder Model structure.
 *
 * Validates: Requirements 2.1, 2.2, 2.3
 *
 * @internal
 * @coversNothing
 */
final class GroupBuyOrderModelTest extends TestCase
{
    private GroupBuyOrder $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new GroupBuyOrder();
    }

    /**
     * Requirement 2.1: Model SHALL 映射到 group_buy_orders 数据表.
     */
    public function testTableName(): void
    {
        self::assertSame('group_buy_orders', $this->model->getTable());
    }

    /**
     * Requirement 2.1: Model SHALL 包含所有迁移中定义的字段.
     */
    public function testFillableContainsAllExpectedFields(): void
    {
        $expectedFields = [
            'group_buy_id',
            'order_id',
            'member_id',
            'group_no',
            'is_leader',
            'quantity',
            'original_price',
            'group_price',
            'total_amount',
            'status',
            'join_time',
            'group_time',
            'pay_time',
            'cancel_time',
            'expire_time',
            'share_code',
            'parent_order_id',
            'remark',
        ];

        $fillable = $this->model->getFillable();

        foreach ($expectedFields as $field) {
            self::assertContains($field, $fillable, "Field '{$field}' should be in fillable");
        }

        // Ensure no unexpected fields
        self::assertCount(\count($expectedFields), $fillable, 'Fillable should contain exactly the expected fields');
    }

    /**
     * Requirement 2.3: is_leader SHALL be cast to boolean.
     */
    public function testCastsIsLeaderToBoolean(): void
    {
        $casts = $this->model->getCasts();

        self::assertArrayHasKey('is_leader', $casts);
        self::assertSame('boolean', $casts['is_leader']);
    }

    /**
     * Requirement 2.3: Time fields SHALL be cast to datetime.
     */
    public function testCastsTimeFieldsToDatetime(): void
    {
        $casts = $this->model->getCasts();

        $datetimeFields = ['join_time', 'group_time', 'pay_time', 'cancel_time', 'expire_time'];

        foreach ($datetimeFields as $field) {
            self::assertArrayHasKey($field, $casts, "Field '{$field}' should have a cast");
            self::assertSame('datetime', $casts[$field], "Field '{$field}' should be cast to datetime");
        }
    }

    /**
     * Requirement 2.3: Numeric fields SHALL be cast to integer.
     */
    public function testCastsNumericFieldsToInteger(): void
    {
        $casts = $this->model->getCasts();

        $integerFields = [
            'group_buy_id',
            'order_id',
            'member_id',
            'quantity',
            'original_price',
            'group_price',
            'total_amount',
            'parent_order_id',
        ];

        foreach ($integerFields as $field) {
            self::assertArrayHasKey($field, $casts, "Field '{$field}' should have a cast");
            self::assertSame('integer', $casts[$field], "Field '{$field}' should be cast to integer");
        }
    }

    /**
     * Requirement 2.2: groupBuy() relationship SHALL be defined and return BelongsTo.
     */
    public function testGroupBuyRelationshipIsDefined(): void
    {
        self::assertTrue(
            method_exists($this->model, 'groupBuy'),
            'GroupBuyOrder should have a groupBuy() relationship method'
        );

        $relation = $this->model->groupBuy();

        self::assertInstanceOf(BelongsTo::class, $relation);
        self::assertSame(GroupBuy::class, \get_class($relation->getRelated()));
        self::assertSame('group_buy_id', $relation->getForeignKeyName());
    }

    /**
     * Requirement 2.2: order() relationship SHALL be defined and return BelongsTo.
     */
    public function testOrderRelationshipIsDefined(): void
    {
        self::assertTrue(
            method_exists($this->model, 'order'),
            'GroupBuyOrder should have an order() relationship method'
        );

        $relation = $this->model->order();

        self::assertInstanceOf(BelongsTo::class, $relation);
        self::assertSame(Order::class, \get_class($relation->getRelated()));
        self::assertSame('order_id', $relation->getForeignKeyName());
    }

    /**
     * Requirement 2.2: member() relationship SHALL be defined and return BelongsTo.
     */
    public function testMemberRelationshipIsDefined(): void
    {
        self::assertTrue(
            method_exists($this->model, 'member'),
            'GroupBuyOrder should have a member() relationship method'
        );

        $relation = $this->model->member();

        self::assertInstanceOf(BelongsTo::class, $relation);
        self::assertSame(Member::class, \get_class($relation->getRelated()));
        self::assertSame('member_id', $relation->getForeignKeyName());
    }
}
