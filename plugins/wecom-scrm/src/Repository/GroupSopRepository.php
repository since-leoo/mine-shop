<?php

declare(strict_types=1);

namespace Plugin\WecomScrm\Repository;

use App\Infrastructure\Abstract\IRepository;
use Hyperf\Database\Model\Builder;
use Plugin\WecomScrm\Model\WecomGroupSop;

/** @extends IRepository<WecomGroupSop> */
final class GroupSopRepository extends IRepository
{
    public function __construct(protected readonly WecomGroupSop $model) {}

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(($params['name'] ?? '') !== '', static fn (Builder $q) => $q->where('name', 'like', '%' . $params['name'] . '%'))
            ->when(isset($params['enabled']) && $params['enabled'] !== '', static fn (Builder $q) => $q->where('enabled', (bool) $params['enabled']))
            ->orderByDesc('id');
    }
}
