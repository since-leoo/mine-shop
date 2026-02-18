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

namespace App\Domain\Trade\Seckill\Repository;

use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Seckill\SeckillOrder;
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
            ->when(isset($params['activity_id']), static fn (Builder $q) => $q->where('activity_id', $params['activity_id']))
            ->when(isset($params['session_id']), static fn (Builder $q) => $q->where('session_id', $params['session_id']))
            ->when(isset($params['member_id']), static fn (Builder $q) => $q->where('member_id', $params['member_id']))
            ->when(isset($params['order_id']), static fn (Builder $q) => $q->where('order_id', $params['order_id']))
            ->orderBy('id', 'desc');
    }

    /**
     * 导出数据提供者.
     */
    public function getExportData(array $params): iterable
    {
        $query = $this->perQuery($this->getQuery()->with(['activity', 'member']), $params);

        foreach ($query->cursor() as $order) {
            yield $order;
        }
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
