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

namespace HyperfTests\Feature\Domain\Marketing\GroupBuy;

use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Entity\OrderItemEntity;
use Carbon\Carbon;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use App\Domain\Trade\GroupBuy\Entity\GroupBuyEntity;
use App\Domain\Trade\GroupBuy\Service\DomainGroupBuyOrderService;
use App\Domain\Trade\GroupBuy\Service\DomainGroupBuyService;
use App\Infrastructure\Model\GroupBuy\GroupBuyOrder;

/**
 * Feature: group-buy-order, Properties 3-6, 10-11: DomainGroupBuyOrderService 属性测试.
 *
 * - Property 3: 不可参与的活动被拒绝
 * - Property 4: SKU 不匹配被拒绝
 * - Property 5: 库存不足被拒绝
 * - Property 6: 重复参团被拒绝
 * - Property 10: 开团记录字段正确性
 * - Property 11: 参团记录字段正确性
 *
 * Validates: Requirements 4.4, 4.5, 4.6, 4.8, 7.2, 7.3, 7.4, 7.5
 *
 * @internal
 * @coversNothing
 */
final class DomainGroupBuyOrderServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const ITERATIONS = 100;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // =========================================================================
    // Property 3: 不可参与的活动被拒绝
    // **Validates: Requirements 4.4**
    // =========================================================================

    /**
     * Property 3: 不可参与的活动被拒绝.
     *
     * For any GroupBuyEntity where canJoin() returns false (disabled, non-active status,
     * sold out, or time out of range), validateActivity() should throw RuntimeException.
     *
     * **Validates: Requirements 4.4**
     *
     * @dataProvider provideProperty3CannotJoinActivityIsRejectedCases
     */
    public function testProperty3CannotJoinActivityIsRejected(GroupBuyEntity $entity): void
    {
        self::assertFalse($entity->canJoin(), 'Precondition: entity.canJoin() should be false');

        $groupBuyService = \Mockery::mock(DomainGroupBuyService::class);
        $groupBuyService->shouldReceive('getEntity')->andReturn($entity);

        $service = new DomainGroupBuyOrderService($groupBuyService);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('当前拼团活动不可参与');

        $service->validateActivity($entity->getId(), $entity->getSkuId(), 1, random_int(1, 99999), null);
    }

    /**
     * @return iterable<string, array{GroupBuyEntity}>
     */
    public static function provideProperty3CannotJoinActivityIsRejectedCases(): iterable
    {
        $iterations = 25; // 25 × 4 scenarios = 100

        // Scenario A: isEnabled = false
        for ($i = 0; $i < $iterations; ++$i) {
            $entity = self::makeJoinableEntity();
            $entity->setIsEnabled(false);
            yield "disabled_{$i}" => [$entity];
        }

        // Scenario B: status is not 'active'
        $nonActiveStatuses = ['pending', 'ended', 'sold_out'];
        for ($i = 0; $i < $iterations; ++$i) {
            $entity = self::makeJoinableEntity();
            $entity->setStatus($nonActiveStatuses[$i % \count($nonActiveStatuses)]);
            yield "non_active_status_{$i}" => [$entity];
        }

        // Scenario C: sold out (soldQuantity >= totalQuantity)
        for ($i = 0; $i < $iterations; ++$i) {
            $entity = self::makeJoinableEntity();
            $total = random_int(1, 100);
            $entity->setTotalQuantity($total);
            $entity->setSoldQuantity($total + random_int(0, 10));
            yield "sold_out_{$i}" => [$entity];
        }

        // Scenario D: time expired (endTime in the past)
        for ($i = 0; $i < $iterations; ++$i) {
            $entity = self::makeJoinableEntity();
            $entity->setStartTime(Carbon::now()->subDays(10)->format('Y-m-d H:i:s'));
            $entity->setEndTime(Carbon::now()->subDays(1)->format('Y-m-d H:i:s'));
            yield "time_expired_{$i}" => [$entity];
        }
    }

    // =========================================================================
    // Property 4: SKU 不匹配被拒绝
    // **Validates: Requirements 4.5**
    // =========================================================================

    /**
     * Property 4: SKU 不匹配被拒绝.
     *
     * For any order item whose skuId differs from GroupBuyEntity's skuId,
     * validateActivity() should throw RuntimeException.
     *
     * **Validates: Requirements 4.5**
     *
     * @dataProvider provideProperty4SkuMismatchIsRejectedCases
     */
    public function testProperty4SkuMismatchIsRejected(int $entitySkuId, int $orderSkuId): void
    {
        self::assertNotSame($entitySkuId, $orderSkuId);

        $entity = self::makeJoinableEntity();
        $entity->setSkuId($entitySkuId);

        $groupBuyService = \Mockery::mock(DomainGroupBuyService::class);
        $groupBuyService->shouldReceive('getEntity')->andReturn($entity);

        $service = new DomainGroupBuyOrderService($groupBuyService);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('该商品不在当前拼团活动中');

        $service->validateActivity($entity->getId(), $orderSkuId, 1, random_int(1, 99999), null);
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function provideProperty4SkuMismatchIsRejectedCases(): iterable
    {
        for ($i = 0; $i < 100; ++$i) {
            $entitySkuId = random_int(1, 999_999);
            $orderSkuId = $entitySkuId + random_int(1, 999_999);
            yield "iteration_{$i} (entity={$entitySkuId}, order={$orderSkuId})" => [$entitySkuId, $orderSkuId];
        }
    }

    // =========================================================================
    // Property 5: 库存不足被拒绝
    // **Validates: Requirements 4.6**
    // =========================================================================

    /**
     * Property 5: 库存不足被拒绝.
     *
     * For any GroupBuyEntity where totalQuantity - soldQuantity < requested quantity,
     * validateActivity() should throw RuntimeException.
     *
     * **Validates: Requirements 4.6**
     *
     * @dataProvider provideProperty5InsufficientStockIsRejectedCases
     */
    public function testProperty5InsufficientStockIsRejected(int $totalQuantity, int $soldQuantity, int $requestQuantity): void
    {
        self::assertLessThan($requestQuantity, $totalQuantity - $soldQuantity);

        $entity = self::makeJoinableEntity();
        $entity->setTotalQuantity($totalQuantity);
        $entity->setSoldQuantity($soldQuantity);

        $groupBuyService = \Mockery::mock(DomainGroupBuyService::class);
        $groupBuyService->shouldReceive('getEntity')->andReturn($entity);

        $service = new DomainGroupBuyOrderService($groupBuyService);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('拼团商品库存不足');

        $service->validateActivity($entity->getId(), $entity->getSkuId(), $requestQuantity, random_int(1, 99999), null);
    }

    /**
     * @return iterable<string, array{int, int, int}>
     */
    public static function provideProperty5InsufficientStockIsRejectedCases(): iterable
    {
        for ($i = 0; $i < 100; ++$i) {
            $totalQuantity = random_int(10, 1000);
            $soldQuantity = random_int(0, $totalQuantity - 1);
            $remaining = $totalQuantity - $soldQuantity;
            $requestQuantity = $remaining + random_int(1, 100);
            yield "iteration_{$i} (total={$totalQuantity}, sold={$soldQuantity}, req={$requestQuantity})" => [
                $totalQuantity, $soldQuantity, $requestQuantity,
            ];
        }
    }

    // =========================================================================
    // Property 6: 重复参团被拒绝
    // **Validates: Requirements 4.8**
    // =========================================================================

    /**
     * Property 6: 重复参团被拒绝.
     *
     * For any member who already has a non-cancelled order in the same group buy activity,
     * validateActivity() should throw RuntimeException.
     *
     * Uses partial mock to override hasMemberJoined() which relies on static model calls.
     * BypassFinals is enabled in bootstrap, allowing partial mocking of final classes.
     *
     * **Validates: Requirements 4.8**
     */
    public function testProperty6DuplicateJoinIsRejected(): void
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $groupBuyId = random_int(1, 999_999);
            $memberId = random_int(1, 999_999);

            $entity = self::makeJoinableEntity();
            $entity->setId($groupBuyId);

            $groupBuyService = \Mockery::mock(DomainGroupBuyService::class);
            $groupBuyService->shouldReceive('getEntity')->andReturn($entity);

            $service = \Mockery::mock(DomainGroupBuyOrderService::class, [$groupBuyService])
                ->makePartial()
                ->shouldAllowMockingProtectedMethods();

            $service->shouldReceive('hasMemberJoined')
                ->with($groupBuyId, $memberId)
                ->andReturn(true);

            $threw = false;
            try {
                $service->validateActivity($groupBuyId, $entity->getSkuId(), 1, $memberId, null);
            } catch (\RuntimeException $e) {
                self::assertSame('每人每个活动限参一次', $e->getMessage(), "iteration {$i}");
                $threw = true;
            }
            self::assertTrue($threw, "iteration {$i}: should have thrown RuntimeException");

            \Mockery::close();
        }
    }

    // =========================================================================
    // Property 10: 开团记录字段正确性
    // **Validates: Requirements 7.2, 7.4, 7.5**
    // =========================================================================

    /**
     * Property 10: 开团记录字段正确性.
     *
     * For any leader scenario (no group_no), createGroupBuyOrder() should create a record with:
     * - is_leader = true
     * - group_no is non-empty and starts with "GB"
     * - share_code is non-empty
     * - expire_time = join_time + group_time_limit hours
     * - increaseSoldQuantity called with quantity
     * - increaseGroupCount called once
     *
     * **Validates: Requirements 7.2, 7.4, 7.5**
     */
    public function testProperty10LeaderRecordFieldsCorrectness(): void
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            Carbon::setTestNow(Carbon::create(2025, 1, 15, 12, 0, 0));

            $entityId = random_int(1, 999);
            $orderId = random_int(1, 999_999);
            $memberId = random_int(1, 999_999);
            $skuId = random_int(1, 999_999);
            $quantity = random_int(1, 10);
            $groupPrice = random_int(100, 99999);
            $originalPrice = $groupPrice + random_int(1, 50000);
            $groupTimeLimit = random_int(1, 72);

            $entity = self::makeJoinableEntity();
            $entity->setId($entityId);
            $entity->setSkuId($skuId);
            $entity->setOriginalPrice($originalPrice);
            $entity->setGroupPrice($groupPrice);
            $entity->setGroupTimeLimit($groupTimeLimit);

            $orderEntity = self::makeOrderEntity($orderId, $memberId, $skuId, $quantity, $groupPrice, null);

            $soldQtyArgs = [];
            $groupCountArgs = [];

            $groupBuyService = \Mockery::mock(DomainGroupBuyService::class);
            $groupBuyService->shouldReceive('increaseSoldQuantity')
                ->andReturnUsing(static function (int $id, int $qty) use (&$soldQtyArgs) {
                    $soldQtyArgs = ['id' => $id, 'quantity' => $qty];
                    return true;
                });
            $groupBuyService->shouldReceive('increaseGroupCount')
                ->andReturnUsing(static function (int $id) use (&$groupCountArgs) {
                    $groupCountArgs = ['id' => $id];
                    return true;
                });

            $capturedRecord = null;
            $service = \Mockery::mock(DomainGroupBuyOrderService::class, [$groupBuyService])
                ->makePartial()
                ->shouldAllowMockingProtectedMethods();

            $service->shouldReceive('persistGroupBuyOrder')
                ->andReturnUsing(static function (array $record) use (&$capturedRecord) {
                    $capturedRecord = $record;
                });

            $service->createGroupBuyOrder($orderEntity, $entity);

            self::assertNotNull($capturedRecord, "iteration {$i}: persistGroupBuyOrder should have been called");
            self::assertTrue($capturedRecord['is_leader'], "iteration {$i}: is_leader should be true");
            self::assertNotEmpty($capturedRecord['group_no'], "iteration {$i}: group_no should not be empty");
            self::assertStringStartsWith('GB', $capturedRecord['group_no'], "iteration {$i}: group_no should start with GB");
            self::assertNotEmpty($capturedRecord['share_code'], "iteration {$i}: share_code should not be empty");
            self::assertSame($entityId, $capturedRecord['group_buy_id'], "iteration {$i}: group_buy_id");
            self::assertSame($orderId, $capturedRecord['order_id'], "iteration {$i}: order_id");
            self::assertSame($memberId, $capturedRecord['member_id'], "iteration {$i}: member_id");
            self::assertSame($quantity, $capturedRecord['quantity'], "iteration {$i}: quantity");
            self::assertSame($originalPrice, $capturedRecord['original_price'], "iteration {$i}: original_price");
            self::assertSame($groupPrice, $capturedRecord['group_price'], "iteration {$i}: group_price");
            self::assertSame($groupPrice * $quantity, $capturedRecord['total_amount'], "iteration {$i}: total_amount");
            self::assertSame('pending', $capturedRecord['status'], "iteration {$i}: status");

            $joinTime = $capturedRecord['join_time'];
            $expireTime = $capturedRecord['expire_time'];
            self::assertInstanceOf(Carbon::class, $joinTime);
            self::assertInstanceOf(Carbon::class, $expireTime);
            $expectedExpire = $joinTime->copy()->addHours($groupTimeLimit);
            self::assertTrue(
                $expireTime->equalTo($expectedExpire),
                "iteration {$i}: expire_time should equal join_time + {$groupTimeLimit}h"
            );

            self::assertSame($entityId, $soldQtyArgs['id'] ?? null, "iteration {$i}: increaseSoldQuantity entity ID");
            self::assertSame($quantity, $soldQtyArgs['quantity'] ?? null, "iteration {$i}: increaseSoldQuantity quantity");
            self::assertSame($entityId, $groupCountArgs['id'] ?? null, "iteration {$i}: increaseGroupCount entity ID");

            \Mockery::close();
            Carbon::setTestNow();
        }
    }

    // =========================================================================
    // Property 11: 参团记录字段正确性
    // **Validates: Requirements 7.3, 7.4**
    // =========================================================================

    /**
     * Property 11: 参团记录字段正确性.
     *
     * For any member join scenario (with group_no), createGroupBuyOrder() should create a record with:
     * - is_leader = false
     * - group_no equals the passed-in value
     * - parent_order_id equals the leader's order_id
     * - increaseSoldQuantity called with quantity
     * - increaseGroupCount NOT called
     *
     * **Validates: Requirements 7.3, 7.4**
     */
    public function testProperty11MemberJoinRecordFieldsCorrectness(): void
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            Carbon::setTestNow(Carbon::create(2025, 1, 15, 12, 0, 0));

            $entityId = random_int(1, 999);
            $orderId = random_int(1, 999_999);
            $memberId = random_int(1, 999_999);
            $skuId = random_int(1, 999_999);
            $quantity = random_int(1, 10);
            $groupPrice = random_int(100, 99999);
            $originalPrice = $groupPrice + random_int(1, 50000);
            $groupNo = 'GB' . date('Ymd') . mb_str_pad((string) random_int(0, 99999999), 8, '0', \STR_PAD_LEFT);
            $leaderOrderId = random_int(1, 999_999);

            $entity = self::makeJoinableEntity();
            $entity->setId($entityId);
            $entity->setSkuId($skuId);
            $entity->setOriginalPrice($originalPrice);
            $entity->setGroupPrice($groupPrice);
            $entity->setGroupTimeLimit(24);

            $orderEntity = self::makeOrderEntity($orderId, $memberId, $skuId, $quantity, $groupPrice, $groupNo);

            $soldQtyArgs = [];
            $groupCountCalled = false;

            $groupBuyService = \Mockery::mock(DomainGroupBuyService::class);
            $groupBuyService->shouldReceive('increaseSoldQuantity')
                ->andReturnUsing(static function (int $id, int $qty) use (&$soldQtyArgs) {
                    $soldQtyArgs = ['id' => $id, 'quantity' => $qty];
                    return true;
                });
            $groupBuyService->shouldReceive('increaseGroupCount')
                ->andReturnUsing(static function () use (&$groupCountCalled) {
                    $groupCountCalled = true;
                    return true;
                });

            $leaderStub = \Mockery::mock(GroupBuyOrder::class)->makePartial();
            $leaderStub->order_id = $leaderOrderId;
            $leaderStub->expire_time = Carbon::now()->addHours(24);

            $capturedRecord = null;
            $service = \Mockery::mock(DomainGroupBuyOrderService::class, [$groupBuyService])
                ->makePartial()
                ->shouldAllowMockingProtectedMethods();

            $service->shouldReceive('findLeaderOrder')
                ->with($groupNo)
                ->andReturn($leaderStub);

            $service->shouldReceive('persistGroupBuyOrder')
                ->andReturnUsing(static function (array $record) use (&$capturedRecord) {
                    $capturedRecord = $record;
                });

            $service->createGroupBuyOrder($orderEntity, $entity);

            self::assertNotNull($capturedRecord, "iteration {$i}: persistGroupBuyOrder should have been called");
            self::assertFalse($capturedRecord['is_leader'], "iteration {$i}: is_leader should be false");
            self::assertSame($groupNo, $capturedRecord['group_no'], "iteration {$i}: group_no should equal passed-in value");
            self::assertSame($leaderOrderId, $capturedRecord['parent_order_id'], "iteration {$i}: parent_order_id");
            self::assertSame($entityId, $capturedRecord['group_buy_id'], "iteration {$i}: group_buy_id");
            self::assertSame($orderId, $capturedRecord['order_id'], "iteration {$i}: order_id");
            self::assertSame($memberId, $capturedRecord['member_id'], "iteration {$i}: member_id");
            self::assertSame($quantity, $capturedRecord['quantity'], "iteration {$i}: quantity");
            self::assertSame($originalPrice, $capturedRecord['original_price'], "iteration {$i}: original_price");
            self::assertSame($groupPrice, $capturedRecord['group_price'], "iteration {$i}: group_price");
            self::assertSame($groupPrice * $quantity, $capturedRecord['total_amount'], "iteration {$i}: total_amount");
            self::assertSame('pending', $capturedRecord['status'], "iteration {$i}: status");

            self::assertSame($entityId, $soldQtyArgs['id'] ?? null, "iteration {$i}: increaseSoldQuantity entity ID");
            self::assertSame($quantity, $soldQtyArgs['quantity'] ?? null, "iteration {$i}: increaseSoldQuantity quantity");
            self::assertFalse($groupCountCalled, "iteration {$i}: increaseGroupCount should NOT be called");

            \Mockery::close();
            Carbon::setTestNow();
        }
    }

    // =========================================================================
    // Shared helpers
    // =========================================================================

    /**
     * Creates a GroupBuyEntity that passes canJoin() checks.
     */
    private static function makeJoinableEntity(): GroupBuyEntity
    {
        $entity = new GroupBuyEntity();
        $entity->setId(random_int(1, 999_999));
        $entity->setTitle('测试拼团活动');
        $entity->setProductId(random_int(1, 999));
        $entity->setSkuId(random_int(1, 999_999));
        $entity->setOriginalPrice(10000);
        $entity->setGroupPrice(8000);
        $entity->setMinPeople(2);
        $entity->setMaxPeople(10);
        $entity->setStartTime(Carbon::now()->subDays(1)->format('Y-m-d H:i:s'));
        $entity->setEndTime(Carbon::now()->addDays(7)->format('Y-m-d H:i:s'));
        $entity->setGroupTimeLimit(24);
        $entity->setStatus('active');
        $entity->setTotalQuantity(100);
        $entity->setSoldQuantity(0);
        $entity->setIsEnabled(true);

        return $entity;
    }

    /**
     * Creates an OrderEntity for testing createGroupBuyOrder.
     */
    private static function makeOrderEntity(
        int $orderId,
        int $memberId,
        int $skuId,
        int $quantity,
        int $groupPrice,
        ?string $groupNo,
    ): OrderEntity {
        $item = new OrderItemEntity();
        $item->setSkuId($skuId);
        $item->setProductId(random_int(1, 999));
        $item->setProductName('测试商品');
        $item->setSkuName('默认规格');
        $item->setUnitPrice($groupPrice);
        $item->setQuantity($quantity);

        $entity = new OrderEntity();
        $entity->setId($orderId);
        $entity->setMemberId($memberId);
        $entity->setOrderType('group_buy');
        $entity->setItems($item);

        if ($groupNo !== null) {
            $entity->setExtra('group_no', $groupNo);
        }

        return $entity;
    }
}
