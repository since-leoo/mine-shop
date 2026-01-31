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

namespace App\Domain\Permission\Repository;

use App\Domain\Auth\Enum\Status;
use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Permission\Menu;
use Hyperf\Collection\Arr;
use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Builder;

final class MenuRepository extends IRepository
{
    public function __construct(protected readonly Menu $model) {}

    public function create(array $payload): Menu
    {
        return $this->model->newQuery()->create($payload);
    }

    public function updateById(mixed $id, array $payload): bool
    {
        return (bool) $this->getQuery()->whereKey($id)->first()?->update($payload);
    }

    public function deleteByIds(array $ids): int
    {
        return $this->deleteById($ids);
    }

    public function enablePageOrderBy(): bool
    {
        return false;
    }

    public function list(array $params = []): Collection
    {
        return $this->perQuery($this->getQuery(), $params)->orderBy('sort')->get();
    }

    public function listByCodes(array $codes): Collection
    {
        return $this->list(['code' => $codes]);
    }

    public function handleSearch(Builder $query, array $params): Builder
    {
        $whereInName = static function (Builder $query, array|string $code) {
            $query->whereIn('name', Arr::wrap($code));
        };
        return $query
            ->when(Arr::get($params, 'sortable'), static function (Builder $query, array $sortable) {
                $query->orderBy(key($sortable), current($sortable));
            })
            ->when(Arr::get($params, 'code'), $whereInName)
            ->when(Arr::get($params, 'name'), $whereInName)
            ->when(Arr::get($params, 'children'), static function (Builder $query) {
                $query->with('children');
            })
            ->when(Arr::get($params, 'status'), static function (Builder $query, Status $status) {
                $query->where('status', $status);
            })
            ->when(Arr::has($params, 'parent_id'), static function (Builder $query) use ($params) {
                $query->where('parent_id', Arr::get($params, 'parent_id'));
            });
    }

    public function allTree(): \Hyperf\Database\Model\Collection
    {
        return $this->model
            ->newQuery()
            ->where('parent_id', 0)
            ->with('children')
            ->get();
    }

    public function getButtonIdsByParent(int $parentId): array
    {
        return $this->getQuery()
            ->where('parent_id', $parentId)
            ->whereJsonContains('meta->type', 'B')
            ->pluck('id')
            ->toArray();
    }
}
