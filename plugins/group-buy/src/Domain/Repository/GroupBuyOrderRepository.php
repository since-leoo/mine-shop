<?php

declare(strict_types=1);

namespace Plugin\Since\GroupBuy\Domain\Repository;

use App\Infrastructure\Abstract\IRepository;
use Hyperf\Database\Model\Builder;
use Plugin\Since\GroupBuy\Infrastructure\Model\GroupBuyOrder;

/**
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

    public function findByOrderId(int $orderId): ?GroupBuyOrder
    {
        return GroupBuyOrder::where('order_id', $orderId)->first();
    }
}
