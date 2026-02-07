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

namespace App\Domain\Infrastructure\SystemSetting\Repository;

use App\Domain\Infrastructure\SystemSetting\Entity\SystemSettingEntity;
use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Setting\SystemSetting;
use Hyperf\Collection\Collection;

/**
 * @extends IRepository<SystemSetting>
 */
final class SystemSettingRepository extends IRepository
{
    public function __construct(protected readonly SystemSetting $model) {}

    public function handleItems(Collection $items): Collection
    {
        return $items->map(static fn (SystemSetting $model) => $model->toArray());
    }

    public function findEntityByKey(string $key): ?SystemSettingEntity
    {
        /** @var null|SystemSetting $model */
        $model = $this->getQuery()->where('key', $key)->first();

        return $model ? $this->toEntity($model) : null;
    }

    /**
     * @return SystemSettingEntity[]
     */
    public function findByGroup(string $group): array
    {
        return $this->getQuery()
            ->where('group', $group)
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->map(fn (SystemSetting $model) => $this->toEntity($model))
            ->toArray();
    }

    public function saveEntity(SystemSettingEntity $entity): SystemSettingEntity
    {
        /** @var SystemSetting $model */
        $model = $this->getQuery()->where('key', $entity->getKey())->first() ?? $this->model->newInstance();

        $model->fill($entity->toArray());
        $model->save();
        $model->refresh();

        return $this->toEntity($model);
    }

    /**
     * @return SystemSettingEntity[]
     */
    public function allEntities(): array
    {
        return $this->getQuery()
            ->orderBy('group')
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->map(fn (SystemSetting $model) => $this->toEntity($model))
            ->toArray();
    }

    private function toEntity(SystemSetting $model): SystemSettingEntity
    {
        return SystemSettingEntity::fromModel($model);
    }
}
