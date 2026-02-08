<?php

declare(strict_types=1);

namespace Plugin\Since\GroupBuy\Domain\Service;

use Carbon\Carbon;
use Hyperf\Collection\Collection;
use Plugin\Since\GroupBuy\Infrastructure\Model\GroupBuyOrder;

final class DomainGroupBuyLifecycleService
{
    public function __construct(
        private readonly DomainGroupBuyService $groupBuyService,
    ) {}

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
