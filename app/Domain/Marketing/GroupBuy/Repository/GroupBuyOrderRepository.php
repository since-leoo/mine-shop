<?php

declare(strict_types=1);

namespace App\Domain\Marketing\GroupBuy\Repository;

use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\GroupBuy\GroupBuyOrder;
use Hyperf\Database\Model\Builder;

/**
 * 团购订单仓储.
 *
 * @extends IRepository<GroupBuyOrder>
 */
final class GroupBuyOrderRepository extends IRepository
{
    public function __construct(protected readonly GroupBuyOrder $model) {}

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(isset($params['group_buy_id']), static fn (Builder $q) => $q->where('group_buy_id', $params['group_buy_id']))
            ->when(isset($params['order_id']), static fn (Builder $q) => $q->where('order_id', $params['order_id']))
            ->when(isset($params['member_id']), static fn (Builder $q) => $q->where('member_id', $params['member_id']))
            ->orderBy('id', 'desc');
    }

    /**
     * 根据主订单 ID 查找团购订单记录.
     */
    public function findByOrderId(int $orderId): ?GroupBuyOrder
    {
        return GroupBuyOrder::where('order_id', $orderId)->first();
    }
}
