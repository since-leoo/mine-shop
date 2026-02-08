<?php

declare(strict_types=1);

namespace Plugin\Since\Seckill\Domain\Repository;

use App\Infrastructure\Abstract\IRepository;
use Plugin\Since\Seckill\Infrastructure\Model\SeckillOrder;
use Hyperf\Database\Model\Builder;

/**
 * @extends IRepository<SeckillOrder>
 */
final class SeckillOrderRepository extends IRepository
{
    public function __construct(protected readonly SeckillOrder $model) {}

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(isset($params['session_id']), static fn (Builder $q) => $q->where('session_id', $params['session_id']))
            ->when(isset($params['member_id']), static fn (Builder $q) => $q->where('member_id', $params['member_id']))
            ->when(isset($params['order_id']), static fn (Builder $q) => $q->where('order_id', $params['order_id']))
            ->orderBy('id', 'desc');
    }

    public function createOrder(array $data): SeckillOrder
    {
        return SeckillOrder::create($data);
    }

    public function getMemberPurchasedQuantity(int $sessionId, int $memberId, int $seckillProductId): int
    {
        return (int) SeckillOrder::where('session_id', $sessionId)
            ->where('member_id', $memberId)->where('seckill_product_id', $seckillProductId)
            ->whereNotIn('status', ['cancelled'])->sum('quantity');
    }

    public function findByOrderId(int $orderId): ?SeckillOrder
    {
        return SeckillOrder::where('order_id', $orderId)->first();
    }
}
