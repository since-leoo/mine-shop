<?php

declare(strict_types=1);

namespace Plugin\Since\GroupBuy\Domain\Api\Query;

use Carbon\Carbon;
use Plugin\Since\GroupBuy\Infrastructure\Model\GroupBuy;
use Plugin\Since\GroupBuy\Infrastructure\Model\GroupBuyOrder;

final class DomainApiGroupBuyGroupsService
{
    /**
     * 获取某个拼团活动正在进行中的团（可参团）.
     *
     * @return array<int, array{group_no: string, leader_nickname: string, leader_avatar: string, joined_count: int, need_count: int, expire_time: string}>
     */
    public function getOngoingGroups(int $activityId, int $limit = 10): array
    {
        $groupBuy = GroupBuy::find($activityId);
        if (! $groupBuy || $groupBuy->status !== 'active') {
            return [];
        }

        $minPeople = (int) $groupBuy->min_people;
        $now = Carbon::now();

        // 查找该活动下所有待成团的团长订单（未过期）
        $leaderOrders = GroupBuyOrder::with('member:id,nickname,avatar')
            ->where('group_buy_id', $activityId)
            ->where('is_leader', true)
            ->where('status', 'paid')
            ->where('expire_time', '>', $now)
            ->orderByDesc('join_time')
            ->limit($limit)
            ->get();

        if ($leaderOrders->isEmpty()) {
            return [];
        }

        $groupNos = $leaderOrders->pluck('group_no')->toArray();

        // 统计每个团已参团人数
        $joinedCounts = GroupBuyOrder::whereIn('group_no', $groupNos)
            ->whereIn('status', ['paid', 'grouped'])
            ->selectRaw('group_no, count(*) as cnt')
            ->groupBy('group_no')
            ->pluck('cnt', 'group_no')
            ->toArray();

        return $leaderOrders->map(static function (GroupBuyOrder $order) use ($joinedCounts, $minPeople) {
            $joined = (int) ($joinedCounts[$order->group_no] ?? 1);
            $need = max(0, $minPeople - $joined);

            return [
                'group_no' => $order->group_no,
                'leader_nickname' => $order->member?->nickname ?? '拼团用户',
                'leader_avatar' => $order->member?->avatar ?? '',
                'joined_count' => $joined,
                'need_count' => $need,
                'expire_time' => $order->expire_time?->toDateTimeString() ?? '',
            ];
        })->toArray();
    }
}
