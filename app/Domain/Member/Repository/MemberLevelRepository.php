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

namespace App\Domain\Member\Repository;

use App\Domain\Member\Entity\MemberLevelEntity;
use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Member\MemberLevel;
use Hyperf\Database\Model\Builder;

/**
 * @extends IRepository<MemberLevel>
 */
final class MemberLevelRepository extends IRepository
{
    public function __construct(protected readonly MemberLevel $model) {}

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(! empty($params['keyword']), static function (Builder $q) use ($params) {
                $keyword = trim((string) $params['keyword']);
                $q->where('name', 'like', '%' . $keyword . '%');
            })
            ->when(! empty($params['status']), static fn (Builder $q) => $q->where('status', $params['status']))
            ->orderByDesc('sort_order')
            ->orderBy('growth_value_min');
    }

    public function save(MemberLevelEntity $entity): MemberLevel
    {
        return $this->model->newQuery()->create($entity->toArray());
    }

    public function updateEntity(MemberLevelEntity $entity): bool
    {
        return $this->updateById($entity->getId(), $entity->toArray());
    }
}
