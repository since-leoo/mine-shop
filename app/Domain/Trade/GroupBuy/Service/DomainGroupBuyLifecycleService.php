<?php

declare(strict_types=1);

namespace App\Domain\Trade\GroupBuy\Service;

use App\Domain\Trade\GroupBuy\Repository\GroupBuyOrderRepository;
use Carbon\Carbon;

final class DomainGroupBuyLifecycleService
{
    public function __construct(
        private readonly DomainGroupBuyService $groupBuyService,
        private readonly GroupBuyOrderRepository $groupBuyOrderRepository,
    ) {}

    /** 返回需要退款的失败团订单；应用层事务覆盖状态变更和退款申请。 */
    public function onOrderPaid(int $orderId): ?int
    {
        $row = $this->groupBuyOrderRepository->findByOrderId($orderId);
        if ($row === null) {
            return null;
        }
        $this->groupBuyService->lock((int) $row->group_buy_id);
        $row = $this->groupBuyOrderRepository->findByOrderId($orderId);
        if ($row->status === 'failed') {
            return $orderId;
        }
        if ($row->status !== 'pending') {
            return null;
        }
        $this->groupBuyOrderRepository->markPaidByOrderId($orderId);
        $activity = $this->groupBuyService->getEntity((int) $row->group_buy_id);
        if ($this->groupBuyOrderRepository->hasGrouped($row->group_no) || ! $row->expire_time?->isPast()) {
            $this->checkAndCompleteGroup($row->group_no, $activity->getId(), $activity->getMinPeople());
        }
        return null;
    }

    public function checkAndCompleteGroup(string $groupNo, int $groupBuyId, int $minPeople): void
    {
        if ($this->groupBuyOrderRepository->countPaidByGroupNo($groupNo) < $minPeople) {
            return;
        }
        $alreadyGrouped = $this->groupBuyOrderRepository->hasGrouped($groupNo);
        $this->groupBuyOrderRepository->markGroupedByGroupNo($groupNo);
        if (! $alreadyGrouped) {
            $this->groupBuyService->increaseSuccessGroupCount($groupBuyId);
        }
    }

    public function onOrderCancelled(int $orderId): void
    {
        $row = $this->groupBuyOrderRepository->findByOrderId($orderId);
        if ($row === null) {
            return;
        }
        $this->groupBuyService->lock((int) $row->group_buy_id);
        $row = $this->groupBuyOrderRepository->findByOrderId($orderId);
        if ($row->status !== 'pending') {
            return;
        }
        $this->groupBuyOrderRepository->updateById($row->id, ['status' => 'cancelled', 'cancel_time' => Carbon::now()]);
        $this->groupBuyService->releaseQuantity((int) $row->group_buy_id, (int) $row->quantity);
    }

    public function expiredGroups(): array
    {
        return $this->groupBuyOrderRepository->findExpiredPendingGroupNos(Carbon::now());
    }

    public function expireGroup(string $groupNo): array
    {
        $rows = $this->groupBuyOrderRepository->findByGroupNo($groupNo);
        if ($rows === []) {
            return [];
        }
        $this->groupBuyService->lock((int) $rows[0]->group_buy_id);
        $rows = $this->groupBuyOrderRepository->findByGroupNo($groupNo);
        if ($this->groupBuyOrderRepository->hasGrouped($groupNo)) {
            return [];
        }
        $pending = array_filter($rows, static fn ($row) => in_array($row->status, ['pending', 'paid'], true) && $row->expire_time?->isPast());
        if ($pending === []) {
            return [];
        }
        $this->groupBuyOrderRepository->markFailedByGroupNo($groupNo, Carbon::now());
        $this->groupBuyService->releaseQuantity((int) $rows[0]->group_buy_id, array_sum(array_map(static fn ($row) => (int) $row->quantity, $pending)));
        return array_values(array_map(static fn ($row) => (int) $row->order_id, $pending));
    }
}
