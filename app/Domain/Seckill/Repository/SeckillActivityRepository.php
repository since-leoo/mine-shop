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

namespace App\Domain\Seckill\Repository;

use App\Domain\Seckill\Entity\SeckillActivityEntity;
use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Seckill\SeckillActivity;
use Hyperf\Database\Model\Builder;

/**
 * 秒杀活动仓储.
 *
 * @extends IRepository<SeckillActivity>
 */
final class SeckillActivityRepository extends IRepository
{
    public function __construct(protected readonly SeckillActivity $model) {}

    public function createFromEntity(SeckillActivityEntity $entity): SeckillActivity
    {
        $activity = SeckillActivity::create($entity->toArray());
        $entity->setId((int) $activity->id);
        return $activity;
    }

    public function updateFromEntity(SeckillActivityEntity $entity): bool
    {
        $activity = SeckillActivity::find($entity->getId());
        return $activity && $activity->update($entity->toArray());
    }

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(isset($params['title']), static fn (Builder $q) => $q->where('title', 'like', '%' . $params['title'] . '%'))
            ->when(isset($params['keyword']), static fn (Builder $q) => $q->where('title', 'like', '%' . $params['keyword'] . '%'))
            ->when(isset($params['status']), static fn (Builder $q) => $q->where('status', $params['status']))
            ->when(isset($params['is_enabled']), static fn (Builder $q) => $q->where('is_enabled', (bool) $params['is_enabled']))
            ->withCount('sessions')
            ->orderBy('id', 'desc');
    }

    /**
     * 获取活动统计数据.
     */
    public function getStatistics(): array
    {
        return [
            'total' => SeckillActivity::count(),
            'enabled' => SeckillActivity::where('is_enabled', true)->count(),
            'disabled' => SeckillActivity::where('is_enabled', false)->count(),
        ];
    }
}
