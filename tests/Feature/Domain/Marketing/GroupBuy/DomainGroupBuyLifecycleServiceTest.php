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

use App\Domain\Marketing\GroupBuy\Service\DomainGroupBuyLifecycleService;
use App\Domain\Marketing\GroupBuy\Service\DomainGroupBuyService;
use App\Infrastructure\Model\GroupBuy\GroupBuyOrder;
use Carbon\Carbon;
use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Collection as ModelCollection;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Feature: group-buy-order, Properties 12-13: DomainGroupBuyLifecycleService 属性测试.
 *
 * - Property 12: 成团判定触发
 * - Property 13: 超时拼团组被取消
 *
 * Validates: Requirements 8.1, 8.2, 8.3, 9.1, 9.2
 *
 * @internal
 * @coversNothing
 */
final class DomainGroupBuyLifecycleServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const ITERATIONS = 100;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // =========================================================================
    // Property 12: 成团判定触发
    // **Validates: Requirements 8.1, 8.2, 8.3**
    // =========================================================================

    /**
     * Property 12: 成团判定触发（正向 — 达到 minPeople）.
     *
     * For any 拼团组，当该 group_no 下已支付订单数达到 minPeople 时，
     * checkAndCompleteGroup() 应将该组所有订单 status 更新为 grouped，
     * 设置 group_time，并使活动 successGroupCount 增加 1。
     *
     * **Validates: Requirements 8.1, 8.2, 8.3**
     */
    public function testProperty12GroupCompletionTriggeredWhenMinPeopleReached(): void
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            Carbon::setTestNow(Carbon::create(2025, 1, 15, 12, 0, 0)->addMinutes($i));

            $groupNo = 'GB' . date('Ymd') . mb_str_pad((string) random_int(0, 99999999), 8, '0', \STR_PAD_LEFT);
            $groupBuyId = random_int(1, 999_999);
            $minPeople = random_int(2, 20);
            // paidCount >= minPeople
            $paidCount = $minPeople + random_int(0, 10);

            $updatedGroupNo = null;
            $updatedGroupTime = null;
            $successGroupCountId = null;

            $groupBuyService = \Mockery::mock(DomainGroupBuyService::class);
            $groupBuyService->shouldReceive('increaseSuccessGroupCount')
                ->andReturnUsing(static function (int $id) use (&$successGroupCountId) {
                    $successGroupCountId = $id;
                    return true;
                });

            $service = \Mockery::mock(DomainGroupBuyLifecycleService::class, [$groupBuyService])
                ->makePartial()
                ->shouldAllowMockingProtectedMethods();

            $service->shouldReceive('countPaidOrders')
                ->with($groupNo)
                ->andReturn($paidCount);

            $service->shouldReceive('updateGroupOrdersToGrouped')
                ->andReturnUsing(static function (string $gn, Carbon $gt) use (&$updatedGroupNo, &$updatedGroupTime) {
                    $updatedGroupNo = $gn;
                    $updatedGroupTime = $gt;
                });

            $service->checkAndCompleteGroup($groupNo, $groupBuyId, $minPeople);

            // Verify updateGroupOrdersToGrouped was called with correct group_no
            self::assertSame($groupNo, $updatedGroupNo, "iteration {$i}: updateGroupOrdersToGrouped should be called with correct group_no");

            // Verify group_time was set (should be a Carbon instance)
            self::assertInstanceOf(Carbon::class, $updatedGroupTime, "iteration {$i}: group_time should be a Carbon instance");

            // Verify increaseSuccessGroupCount was called with correct groupBuyId
            self::assertSame($groupBuyId, $successGroupCountId, "iteration {$i}: increaseSuccessGroupCount should be called with correct groupBuyId");

            \Mockery::close();
            Carbon::setTestNow();
        }
    }

    /**
     * Property 12: 成团判定触发（反向 — 未达到 minPeople）.
     *
     * For any 拼团组，当该 group_no 下已支付订单数未达到 minPeople 时，
     * checkAndCompleteGroup() 不应更新订单状态，也不应增加 successGroupCount。
     *
     * **Validates: Requirements 8.1, 8.2, 8.3**
     */
    public function testProperty12GroupNotCompletedWhenBelowMinPeople(): void
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $groupNo = 'GB' . date('Ymd') . mb_str_pad((string) random_int(0, 99999999), 8, '0', \STR_PAD_LEFT);
            $groupBuyId = random_int(1, 999_999);
            $minPeople = random_int(2, 20);
            // paidCount < minPeople
            $paidCount = random_int(0, $minPeople - 1);

            $updateCalled = false;
            $successCountCalled = false;

            $groupBuyService = \Mockery::mock(DomainGroupBuyService::class);
            $groupBuyService->shouldReceive('increaseSuccessGroupCount')
                ->andReturnUsing(static function () use (&$successCountCalled) {
                    $successCountCalled = true;
                    return true;
                });

            $service = \Mockery::mock(DomainGroupBuyLifecycleService::class, [$groupBuyService])
                ->makePartial()
                ->shouldAllowMockingProtectedMethods();

            $service->shouldReceive('countPaidOrders')
                ->with($groupNo)
                ->andReturn($paidCount);

            $service->shouldReceive('updateGroupOrdersToGrouped')
                ->andReturnUsing(static function () use (&$updateCalled) {
                    $updateCalled = true;
                });

            $service->checkAndCompleteGroup($groupNo, $groupBuyId, $minPeople);

            // Verify updateGroupOrdersToGrouped was NOT called
            self::assertFalse($updateCalled, "iteration {$i}: updateGroupOrdersToGrouped should NOT be called when paidCount ({$paidCount}) < minPeople ({$minPeople})");

            // Verify increaseSuccessGroupCount was NOT called
            self::assertFalse($successCountCalled, "iteration {$i}: increaseSuccessGroupCount should NOT be called when paidCount ({$paidCount}) < minPeople ({$minPeople})");

            \Mockery::close();
        }
    }

    // =========================================================================
    // Property 13: 超时拼团组被取消
    // **Validates: Requirements 9.1, 9.2**
    // =========================================================================

    /**
     * Property 13: 超时拼团组被取消.
     *
     * For any expire_time 已过且 status 为 pending 的拼团组，
     * cancelExpiredGroups() 应将该组所有订单 status 更新为 failed。
     *
     * **Validates: Requirements 9.1, 9.2**
     */
    public function testProperty13ExpiredGroupsAreCancelled(): void
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            Carbon::setTestNow(Carbon::create(2025, 1, 15, 12, 0, 0)->addMinutes($i));

            // Generate 1-5 expired group_nos
            $groupCount = random_int(1, 5);
            $expiredGroupNos = [];
            for ($g = 0; $g < $groupCount; ++$g) {
                $expiredGroupNos[] = 'GB' . date('Ymd') . mb_str_pad((string) random_int(0, 99999999), 8, '0', \STR_PAD_LEFT);
            }

            // Track which group_nos had updateGroupOrdersToFailed called
            $failedGroupNos = [];
            // Track which group_nos had findOrdersByGroupNo called
            $queriedGroupNos = [];
            // Track refund triggers
            $refundedOrders = [];

            $groupBuyService = \Mockery::mock(DomainGroupBuyService::class);

            $service = \Mockery::mock(DomainGroupBuyLifecycleService::class, [$groupBuyService])
                ->makePartial()
                ->shouldAllowMockingProtectedMethods();

            $service->shouldReceive('findExpiredGroupNos')
                ->andReturn(new Collection($expiredGroupNos));

            // For each group, generate random orders (some paid, some pending)
            $ordersPerGroup = [];
            foreach ($expiredGroupNos as $gno) {
                $orderCount = random_int(1, 5);
                $orders = new ModelCollection();
                for ($o = 0; $o < $orderCount; ++$o) {
                    $order = \Mockery::mock(GroupBuyOrder::class)->makePartial();
                    // Randomly assign status: some 'paid', some 'pending'
                    $order->status = random_int(0, 1) === 1 ? 'paid' : 'pending';
                    $order->id = random_int(1, 999_999);
                    $order->group_no = $gno;
                    $orders->push($order);
                }
                $ordersPerGroup[$gno] = $orders;
            }

            $service->shouldReceive('findOrdersByGroupNo')
                ->andReturnUsing(static function (string $gno) use ($ordersPerGroup, &$queriedGroupNos) {
                    $queriedGroupNos[] = $gno;
                    return $ordersPerGroup[$gno] ?? new ModelCollection();
                });

            $service->shouldReceive('updateGroupOrdersToFailed')
                ->andReturnUsing(static function (string $gno) use (&$failedGroupNos) {
                    $failedGroupNos[] = $gno;
                });

            $service->shouldReceive('triggerRefund')
                ->andReturnUsing(static function ($order) use (&$refundedOrders) {
                    $refundedOrders[] = $order->id;
                });

            $result = $service->cancelExpiredGroups();

            // Verify return count matches number of expired groups
            self::assertSame($groupCount, $result, "iteration {$i}: cancelExpiredGroups should return {$groupCount}");

            // Verify updateGroupOrdersToFailed was called for each expired group_no
            self::assertCount($groupCount, $failedGroupNos, "iteration {$i}: updateGroupOrdersToFailed should be called for each expired group");
            foreach ($expiredGroupNos as $gno) {
                self::assertContains($gno, $failedGroupNos, "iteration {$i}: updateGroupOrdersToFailed should be called for group_no {$gno}");
            }

            // Verify findOrdersByGroupNo was called for each expired group_no
            self::assertCount($groupCount, $queriedGroupNos, "iteration {$i}: findOrdersByGroupNo should be called for each expired group");
            foreach ($expiredGroupNos as $gno) {
                self::assertContains($gno, $queriedGroupNos, "iteration {$i}: findOrdersByGroupNo should be called for group_no {$gno}");
            }

            // Verify triggerRefund was called only for paid orders
            $expectedRefundIds = [];
            foreach ($ordersPerGroup as $orders) {
                foreach ($orders as $order) {
                    if ($order->status === 'paid') {
                        $expectedRefundIds[] = $order->id;
                    }
                }
            }
            sort($expectedRefundIds);
            sort($refundedOrders);
            self::assertSame($expectedRefundIds, $refundedOrders, "iteration {$i}: triggerRefund should be called only for paid orders");

            \Mockery::close();
            Carbon::setTestNow();
        }
    }

    /**
     * Property 13: 超时拼团组被取消（无超时组时返回 0）.
     *
     * When there are no expired groups, cancelExpiredGroups() should return 0
     * and not call any update methods.
     *
     * **Validates: Requirements 9.1, 9.2**
     */
    public function testProperty13NoExpiredGroupsReturnsZero(): void
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $updateCalled = false;

            $groupBuyService = \Mockery::mock(DomainGroupBuyService::class);

            $service = \Mockery::mock(DomainGroupBuyLifecycleService::class, [$groupBuyService])
                ->makePartial()
                ->shouldAllowMockingProtectedMethods();

            $service->shouldReceive('findExpiredGroupNos')
                ->andReturn(new Collection([]));

            $service->shouldReceive('updateGroupOrdersToFailed')
                ->andReturnUsing(static function () use (&$updateCalled) {
                    $updateCalled = true;
                });

            $result = $service->cancelExpiredGroups();

            self::assertSame(0, $result, "iteration {$i}: cancelExpiredGroups should return 0 when no expired groups");
            self::assertFalse($updateCalled, "iteration {$i}: updateGroupOrdersToFailed should NOT be called when no expired groups");

            \Mockery::close();
        }
    }
}
