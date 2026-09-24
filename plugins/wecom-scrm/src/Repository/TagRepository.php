<?php

declare(strict_types=1);

namespace Plugin\WecomScrm\Repository;

use App\Infrastructure\Abstract\IRepository;
use Hyperf\Database\Model\Builder;
use Plugin\WecomScrm\Model\WecomTag;

/** @extends IRepository<WecomTag> */
final class TagRepository extends IRepository
{
    public function __construct(protected readonly WecomTag $model) {}

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(($params['name'] ?? '') !== '', static fn (Builder $q) => $q->where('name', 'like', '%' . $params['name'] . '%'))
            ->when(($params['group_id'] ?? '') !== '', static fn (Builder $q) => $q->where('group_id', $params['group_id']))
            ->orderByDesc('id');
    }

    public function save(array $data, ?int $id = null): WecomTag
    {
        $model = $id === null ? new WecomTag() : $this->model->newQuery()->findOrFail($id);
        $model->fill($data)->save();
        return $model;
    }

    public function delete(int $id): void
    {
        $this->model->newQuery()->findOrFail($id)->delete();
    }
}
