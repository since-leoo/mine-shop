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

namespace App\Domain\Trade\GroupBuy\Repository;

use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\GroupBuy\GroupBuyOrder;
use Carbon\Carbon;
use Hyperf\Database\Model\Builder;

/**
 * @extends IRepository<GroupBuyOrder>
 */
final class GroupBuyOrderRepository extends IRepository
{
    public function __construct(protected readonly GroupBuyOrder $model) {}

    /**
     * 导出数据提供者.
     */
    public function getExportData(array $params): iterable
    {
        $query = $this->perQuery($this->getQuery()->with(['groupBuy', 'member']), $params);

        foreach ($query->cursor() as $order) {
            yield $order;
        }
    }

    /**
     * 通过订单ID查找团购订单.
     */
    public function findByOrderId(int $orderId): ?GroupBuyOrder
    {
        return GroupBuyOrder::where('order_id', $orderId)->first();
    }

    public function createRecord(array $record): GroupBuyOrder
    {
        return GroupBuyOrder::create($record);
    }

    /**
     * 判断用户是否已参与该团购.
     */
    public function hasJoined(int $groupBuyId, int $memberId): bool
    {
        return GroupBuyOrder::where('group_buy_id', $groupBuyId)
            ->where('member_id', $memberId)
            ->whereNotIn('status', ['cancelled', 'failed'])
            ->exists();
    }

    /**
     * @return array<int, GroupBuyOrder>
     */
    public function findActiveByGroupNo(string $groupNo): array
    {
        return GroupBuyOrder::where('group_no', $groupNo)
            ->whereNotIn('status', ['cancelled', 'failed'])
            ->get()
            ->all();
    }

    public function findLeaderByGroupNo(string $groupNo): ?GroupBuyOrder
    {
        return GroupBuyOrder::where('group_no', $groupNo)
            ->where('is_leader', true)
            ->first();
    }

    public function countPaidByGroupNo(string $groupNo): int
    {
        return GroupBuyOrder::where('group_no', $groupNo)
            ->where('status', 'paid')
            ->count();
    }

    public function markGroupedByGroupNo(string $groupNo): int
    {
        return GroupBuyOrder::where('group_no', $groupNo)
            ->update(['status' => 'grouped', 'group_time' => Carbon::now()]);
    }

    /**
     * @return array<int, string>
     */
    public function findExpiredPendingGroupNos(Carbon $now): array
    {
        return GroupBuyOrder::where('expire_time', '<', $now)
            ->where('status', 'pending')
            ->distinct()
            ->pluck('group_no')
            ->all();
    }

    /**
     * @return array<int, GroupBuyOrder>
     */
    public function findByGroupNo(string $groupNo): array
    {
        return GroupBuyOrder::where('group_no', $groupNo)
            ->get()
            ->all();
    }

    public function markFailedByGroupNo(string $groupNo, Carbon $now): int
    {
        return GroupBuyOrder::where('group_no', $groupNo)
            ->update(['status' => 'failed', 'cancel_time' => $now]);
    }

    /**
     * 将团购订单标记为已支付.
     */
    public function markPaidByOrderId(int $orderId): bool
    {
        return (bool) $this->getQuery()
            ->where('order_id', $orderId)
            ->where('status', 'pending')
            ->update(['status' => 'paid', 'pay_time' => Carbon::now()]);
    }

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(isset($params['group_buy_id']), static fn (Builder $q) => $q->where('group_buy_id', $params['group_buy_id']))
            ->when(isset($params['order_id']), static fn (Builder $q) => $q->where('order_id', $params['order_id']))
            ->when(isset($params['member_id']), static fn (Builder $q) => $q->where('member_id', $params['member_id']))
            ->orderBy('id', 'desc');
    }
}
