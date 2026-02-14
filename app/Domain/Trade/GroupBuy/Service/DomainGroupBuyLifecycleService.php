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

namespace App\Domain\Trade\GroupBuy\Service;

use Carbon\Carbon;
use App\Domain\Trade\GroupBuy\Repository\GroupBuyOrderRepository;
use App\Infrastructure\Model\GroupBuy\GroupBuyOrder;

final class DomainGroupBuyLifecycleService
{
    public function __construct(
        private readonly DomainGroupBuyService $groupBuyService,
        private readonly GroupBuyOrderRepository $groupBuyOrderRepository,
    ) {}

    /**
     * 订单支付成功后，更新团购订单状态并检查是否成团.
     */
    public function onOrderPaid(int $orderId): void
    {
        $groupBuyOrder = $this->groupBuyOrderRepository->findByOrderId($orderId);
        if (! $groupBuyOrder || $groupBuyOrder->status !== 'pending') {
            return;
        }

        $this->groupBuyOrderRepository->markPaidByOrderId($orderId);

        try {
            $entity = $this->groupBuyService->getEntity((int) $groupBuyOrder->group_buy_id);
            $this->checkAndCompleteGroup($groupBuyOrder->group_no, $entity->getId(), $entity->getMinPeople());
        } catch (\RuntimeException) {
            // 活动不存在，跳过成团检查
        }
    }

    public function checkAndCompleteGroup(string $groupNo, int $groupBuyId, int $minPeople): void
    {
        $paidCount = GroupBuyOrder::where('group_no', $groupNo)->where('status', 'paid')->count();
        if ($paidCount >= $minPeople) {
            $now = Carbon::now();
            GroupBuyOrder::where('group_no', $groupNo)->update(['status' => 'grouped', 'group_time' => $now]);
            $this->groupBuyService->increaseSuccessGroupCount($groupBuyId);
        }
    }

    public function cancelExpiredGroups(): int
    {
        $now = Carbon::now();
        $expiredGroupNos = GroupBuyOrder::where('expire_time', '<', $now)
            ->where('status', 'pending')->distinct()->pluck('group_no');

        if ($expiredGroupNos->isEmpty()) {
            return 0;
        }

        $processedCount = 0;
        foreach ($expiredGroupNos as $groupNo) {
            $groupOrders = GroupBuyOrder::where('group_no', $groupNo)->get();
            GroupBuyOrder::where('group_no', $groupNo)->update(['status' => 'failed', 'cancel_time' => $now]);
            foreach ($groupOrders as $order) {
                if ($order->status === 'paid') {
                    // TODO: 对接实际退款服务
                }
            }
            ++$processedCount;
        }
        return $processedCount;
    }
}
