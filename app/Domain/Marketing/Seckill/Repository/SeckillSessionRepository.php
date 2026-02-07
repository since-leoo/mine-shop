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

use App\Domain\Marketing\Seckill\Entity\SeckillSessionEntity;
use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Seckill\SeckillSession;
use Hyperf\Database\Model\Builder;

/**
 * 秒杀场次仓储.
 *
 * @extends IRepository<SeckillSession>
 */
final class SeckillSessionRepository extends IRepository
{
    public function __construct(protected readonly SeckillSession $model) {}

    public function createFromEntity(SeckillSessionEntity $entity): SeckillSession
    {
        $session = SeckillSession::create($entity->toArray());
        $entity->setId((int) $session->id);
        return $session;
    }

    public function updateFromEntity(SeckillSessionEntity $entity): bool
    {
        $session = SeckillSession::find($entity->getId());
        return $session && $session->update($entity->toArray());
    }

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(isset($params['activity_id']), static fn (Builder $q) => $q->where('activity_id', $params['activity_id']))
            ->when(isset($params['status']), static fn (Builder $q) => $q->where('status', $params['status']))
            ->when(isset($params['is_enabled']), static fn (Builder $q) => $q->where('is_enabled', (bool) $params['is_enabled']))
            ->with('activity')
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('start_time', 'desc');
    }

    /**
     * 获取指定活动的场次列表.
     *
     * @return SeckillSession[]
     */
    public function findByActivityId(int $activityId): array
    {
        return SeckillSession::where('activity_id', $activityId)
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('start_time')
            ->get()
            ->all();
    }

    /**
     * 统计活动下的场次数.
     */
    public function countByActivityId(int $activityId): int
    {
        return SeckillSession::where('activity_id', $activityId)->count();
    }

    /**
     * 更新场次库存统计.
     */
    public function updateQuantityStats(int $sessionId): void
    {
        $session = SeckillSession::find($sessionId);
        if (! $session) {
            return;
        }

        $products = $session->products()->where('is_enabled', true)->get();
        $totalQuantity = $products->sum('quantity');
        $soldQuantity = $products->sum('sold_quantity');

        $session->update([
            'total_quantity' => $totalQuantity,
            'sold_quantity' => $soldQuantity,
        ]);
    }
}
