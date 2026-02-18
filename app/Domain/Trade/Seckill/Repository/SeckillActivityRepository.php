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

use App\Domain\Trade\Seckill\Entity\SeckillActivityEntity;
use App\Domain\Trade\Seckill\Enum\SeckillStatus;
use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Seckill\SeckillActivity;
use Hyperf\Database\Model\Builder;

/**
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

    public function toEntity(SeckillActivity $model): SeckillActivityEntity
    {
        return SeckillActivityEntity::reconstitute(
            id: $model->id,
            title: $model->title,
            description: $model->description,
            status: $model->status,
            isEnabled: $model->is_enabled,
            rulesData: $model->rules,
            remark: $model->remark,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at
        );
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
     * 导出数据提供者.
     */
    public function getExportData(array $params): iterable
    {
        $query = $this->perQuery($this->getQuery(), $params);

        foreach ($query->cursor() as $activity) {
            yield $activity;
        }
    }

    public function getStatistics(): array
    {
        return [
            'total' => SeckillActivity::count(),
            'enabled' => SeckillActivity::where('is_enabled', true)->count(),
            'disabled' => SeckillActivity::where('is_enabled', false)->count(),
            'pending' => SeckillActivity::where('status', 'pending')->count(),
            'active' => SeckillActivity::where('status', 'active')->count(),
            'ended' => SeckillActivity::where('status', 'ended')->count(),
            'cancelled' => SeckillActivity::where('status', 'cancelled')->count(),
        ];
    }

    public function findPendingEnabledActivities(): array
    {
        return SeckillActivity::where('status', SeckillStatus::PENDING->value)->where('is_enabled', true)->get()->all();
    }

    public function findActiveActivities(): array
    {
        return SeckillActivity::where('status', SeckillStatus::ACTIVE->value)->get()->all();
    }

    public function findLatestEnabledActiveOrPending(): ?SeckillActivity
    {
        return SeckillActivity::where('is_enabled', true)
            ->whereIn('status', [SeckillStatus::ACTIVE->value, SeckillStatus::PENDING->value])
            ->orderBy('id', 'desc')->first();
    }
}
