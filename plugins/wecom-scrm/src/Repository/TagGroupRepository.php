<?php

declare(strict_types=1);

namespace Plugin\WecomScrm\Repository;

use App\Infrastructure\Abstract\IRepository;
use Hyperf\Database\Model\Builder;
use Plugin\WecomScrm\Model\WecomTagGroup;

/** @extends IRepository<WecomTagGroup> */
final class TagGroupRepository extends IRepository
{
    public function __construct(protected readonly WecomTagGroup $model) {}

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(($params['name'] ?? '') !== '', static fn (Builder $q) => $q->where('name', 'like', '%' . $params['name'] . '%'))
            ->orderByDesc('id');
    }

    public function save(array $data, ?int $id = null): WecomTagGroup
    {
        $model = $id === null ? new WecomTagGroup() : $this->model->newQuery()->findOrFail($id);
        $model->fill($data)->save();
        return $model;
    }

    public function delete(int $id): void
    {
        $this->model->newQuery()->findOrFail($id)->delete();
    }
}
