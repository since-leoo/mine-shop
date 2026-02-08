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

namespace App\Domain\Marketing\GroupBuy\Service;

use App\Infrastructure\Model\GroupBuy\GroupBuyOrder;
use Carbon\Carbon;
use Hyperf\Collection\Collection;

/**
 * 拼团组生命周期领域服务.
 *
 * 负责成团判定和超时取消，与订单创建流程解耦。
 */
final class DomainGroupBuyLifecycleService
{
    public function __construct(
        private readonly DomainGroupBuyService $groupBuyService,
    ) {}

    /**
     * 检查成团并触发状态更新（支付成功后调用）.
     *
     * 如果逻辑较重，调用方使用 co() 协程异步处理。
     *
     * @param string $groupNo 团号
     * @param int $groupBuyId 拼团活动 ID
     * @param int $minPeople 最低成团人数
     */
    public function checkAndCompleteGroup(string $groupNo, int $groupBuyId, int $minPeople): void
    {
        // 1. 查询该 group_no 下 status = paid 的订单数
        $paidCount = $this->countPaidOrders($groupNo);

        // 2. 如果 >= minPeople，触发成团
        if ($paidCount >= $minPeople) {
            $now = Carbon::now();

            // 更新所有该 group_no 的订单 status 为 grouped，设置 group_time
            $this->updateGroupOrdersToGrouped($groupNo, $now);

            // 增加成功成团数
            $this->groupBuyService->increaseSuccessGroupCount($groupBuyId);
        }
    }

    /**
     * 取消超时拼团组.
     *
     * 扫描 expire_time 已过且 status 为 pending 的拼团组，
     * 将该组所有订单状态更新为 failed，对已支付订单触发退款。
     *
     * @return int 处理的拼团组数量
     */
    public function cancelExpiredGroups(): int
    {
        $now = Carbon::now();

        // 1. 查询 expire_time < now 且 status = pending 的 group_no 列表（去重）
        $expiredGroupNos = $this->findExpiredGroupNos($now);

        if ($expiredGroupNos->isEmpty()) {
            return 0;
        }

        $processedCount = 0;

        foreach ($expiredGroupNos as $groupNo) {
            // 2. 获取该组所有订单
            $groupOrders = $this->findOrdersByGroupNo($groupNo);

            // 3. 批量更新 status 为 failed，设置 cancel_time
            $this->updateGroupOrdersToFailed($groupNo, $now);

            // 4. 对已支付的订单触发退款
            foreach ($groupOrders as $order) {
                if ($order->status === 'paid') {
                    $this->triggerRefund($order);
                }
            }

            ++$processedCount;
        }

        return $processedCount;
    }

    /**
     * 查询指定团号下已支付的订单数.
     *
     * @param string $groupNo 团号
     * @return int 已支付订单数
     */
    private function countPaidOrders(string $groupNo): int
    {
        return GroupBuyOrder::where('group_no', $groupNo)
            ->where('status', 'paid')
            ->count();
    }

    /**
     * 将指定团号的所有订单状态更新为 grouped，设置 group_time.
     *
     * @param string $groupNo 团号
     * @param Carbon $groupTime 成团时间
     */
    private function updateGroupOrdersToGrouped(string $groupNo, Carbon $groupTime): void
    {
        GroupBuyOrder::where('group_no', $groupNo)
            ->update([
                'status' => 'grouped',
                'group_time' => $groupTime,
            ]);
    }

    /**
     * 查询超时未成团的 group_no 列表（去重）.
     *
     * @param Carbon $now 当前时间
     * @return Collection<int, string> 超时的团号列表
     */
    private function findExpiredGroupNos(Carbon $now): Collection
    {
        return GroupBuyOrder::where('expire_time', '<', $now)
            ->where('status', 'pending')
            ->distinct()
            ->pluck('group_no');
    }

    /**
     * 查询指定团号的所有订单.
     *
     * @param string $groupNo 团号
     * @return \Hyperf\Database\Model\Collection<int, GroupBuyOrder> 订单集合
     */
    private function findOrdersByGroupNo(string $groupNo): \Hyperf\Database\Model\Collection
    {
        return GroupBuyOrder::where('group_no', $groupNo)->get();
    }

    /**
     * 将指定团号的所有订单状态更新为 failed，设置 cancel_time.
     *
     * @param string $groupNo 团号
     * @param Carbon $cancelTime 取消时间
     */
    private function updateGroupOrdersToFailed(string $groupNo, Carbon $cancelTime): void
    {
        GroupBuyOrder::where('group_no', $groupNo)
            ->update([
                'status' => 'failed',
                'cancel_time' => $cancelTime,
            ]);
    }

    /**
     * 触发退款流程.
     *
     * TODO: 对接实际退款服务（如 DomainOrderPaymentService::addRefundAmount）
     *
     * @param GroupBuyOrder $order 需要退款的订单
     */
    private function triggerRefund(GroupBuyOrder $order): void
    {
        // 退款逻辑待对接实际退款服务
        // 当前标记为需要退款，后续可通过事件或直接调用退款服务处理
    }
}
