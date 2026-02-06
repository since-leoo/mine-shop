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

namespace App\Infrastructure\Abstract;

use App\Infrastructure\Traits\BootTrait;
use App\Infrastructure\Traits\RepositoryOrderByTrait;
use Hyperf\Collection\Collection;
use Hyperf\Contract\LengthAwarePaginatorInterface;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Model;
use Hyperf\DbConnection\Traits\HasContainer;
use Hyperf\Paginator\AbstractPaginator;

/**
 * @template T of Model
 * @property Model $model
 */
abstract class IRepository
{
    use BootTrait;
    use HasContainer;
    use RepositoryOrderByTrait;

    public const PER_PAGE_PARAM_NAME = 'per_page';

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query;
    }

    public function handleItems(Collection $items): Collection
    {
        return $items;
    }

    public function handlePage(LengthAwarePaginatorInterface $paginator): array
    {
        if ($paginator instanceof AbstractPaginator) {
            $items = $paginator->getCollection();
        } else {
            $items = Collection::make($paginator->items());
        }
        $items = $this->handleItems($items);
        return [
            'list' => $items->toArray(),
            'total' => $paginator->total(),
        ];
    }

    public function list(array $params = []): Collection
    {
        return $this->handleItems($this->perQuery($this->getQuery(), $params)->get());
    }

    public function count(array $params = []): int
    {
        return $this->perQuery($this->getQuery(), $params)->count();
    }

    public function page(array $params = [], ?int $page = null, ?int $pageSize = null): array
    {
        $result = $this->perQuery($this->getQuery(), $params)->paginate(
            perPage: $pageSize,
            pageName: static::PER_PAGE_PARAM_NAME,
            page: $page,
        );
        return $this->handlePage($result);
    }

    public function findById(int $id): ?object
    {
        return $this->getQuery()->whereKey($id)->first();
    }

    public function findByIdForLock(mixed $id)
    {
        return $this->getQuery()->whereKey($id)->lockForUpdate()->first();
    }

    public function create(array $payload): mixed
    {
        return $this->model->newQuery()->create($payload);
    }

    public function updateById(int $id, array $data): bool
    {
        return (bool) $this->getQuery()->whereKey($id)->first()?->update($data);
    }

    /**
     * @return null|Model
     */
    public function saveById(mixed $id, array $data): mixed
    {
        $model = $this->getQuery()->whereKey($id)->first();
        if ($model) {
            $model->fill($data)->save();
            return $model;
        }
        return null;
    }

    public function deleteById(mixed $id): int
    {
        // @phpstan-ignore-next-line
        return $this->model::destroy($id);
    }

    public function deleteByIds(array $ids): int
    {
        array_map(function ($id) {
            $info = $this->findById($id);
            $info && $info->delete();
        }, $ids);

        return \count($ids);
    }

    public function forceDeleteById(mixed $id): bool
    {
        return (bool) $this->getQuery()->whereKey($id)->forceDelete();
    }

    public function findByField(mixed $id, string $field): mixed
    {
        return $this->getQuery()->whereKey($id)->value($field);
    }

    /**
     * @return null|Model
     */
    public function findByFilter(array $params): mixed
    {
        return $this->perQuery($this->getQuery(), $params)->first();
    }

    public function perQuery(Builder $query, array $params): Builder
    {
        $this->startBoot($query, $params);
        return $this->handleSearch($query, $params);
    }

    public function getQuery(): Builder
    {
        return $this->model->newQuery();
    }

    public function existsById(mixed $id): bool
    {
        return (bool) $this->getQuery()->whereKey($id)->exists();
    }

    /**
     * @return Model
     */
    public function getModel()
    {
        return $this->model;
    }
}
