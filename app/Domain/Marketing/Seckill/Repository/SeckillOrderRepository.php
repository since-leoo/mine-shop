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

namespace App\Domain\Marketing\Seckill\Repository;

use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Seckill\SeckillOrder;
use Hyperf\Database\Model\Builder;

/**
 * 秒杀订单仓储.
 *
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

    /**
     * 创建秒杀订单记录.
     */
    public function createOrder(array $data): SeckillOrder
    {
        return SeckillOrder::create($data);
    }

    /**
     * 查询会员在指定场次下某秒杀商品的已购数量.
     */
    public function getMemberPurchasedQuantity(int $sessionId, int $memberId, int $seckillProductId): int
    {
        return (int) SeckillOrder::where('session_id', $sessionId)
            ->where('member_id', $memberId)
            ->where('seckill_product_id', $seckillProductId)
            ->whereNotIn('status', ['cancelled'])
            ->sum('quantity');
    }
}
