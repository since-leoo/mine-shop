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

use App\Domain\Trade\GroupBuy\Repository\GroupBuyOrderRepository;
use Carbon\Carbon;

final class DomainGroupBuyLifecycleService
{
    public function __construct(
        private readonly DomainGroupBuyService $groupBuyService,
        private readonly GroupBuyOrderRepository $groupBuyOrderRepository,
    ) {}

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
            // Ignore missing activity on lifecycle callback.
        }
    }

    public function checkAndCompleteGroup(string $groupNo, int $groupBuyId, int $minPeople): void
    {
        $paidCount = $this->groupBuyOrderRepository->countPaidByGroupNo($groupNo);
        if ($paidCount >= $minPeople) {
            $this->groupBuyOrderRepository->markGroupedByGroupNo($groupNo);
            $this->groupBuyService->increaseSuccessGroupCount($groupBuyId);
        }
    }

    public function cancelExpiredGroups(): int
    {
        $now = Carbon::now();
        $expiredGroupNos = $this->groupBuyOrderRepository->findExpiredPendingGroupNos($now);

        if ($expiredGroupNos === []) {
            return 0;
        }

        $processedCount = 0;
        foreach ($expiredGroupNos as $groupNo) {
            $groupOrders = $this->groupBuyOrderRepository->findByGroupNo($groupNo);
            $this->groupBuyOrderRepository->markFailedByGroupNo($groupNo, $now);

            foreach ($groupOrders as $order) {
                if ($order->status === 'paid') {
                    // TODO: integrate refund service for paid but failed groups.
                }
            }

            ++$processedCount;
        }

        return $processedCount;
    }
}
