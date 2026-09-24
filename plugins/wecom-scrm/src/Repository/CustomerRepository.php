<?php

declare(strict_types=1);

namespace Plugin\WecomScrm\Repository;

use App\Infrastructure\Abstract\IRepository;
use Hyperf\Database\Model\Builder;
use Plugin\WecomScrm\Model\WecomCustomer;

/** @extends IRepository<WecomCustomer> */
final class CustomerRepository extends IRepository
{
    public function __construct(protected readonly WecomCustomer $model) {}

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(($params['name'] ?? '') !== '', static fn (Builder $q) => $q->where('name', 'like', '%' . $params['name'] . '%'))
            ->when(($params['follow_userid'] ?? '') !== '', static fn (Builder $q) => $q->where('follow_userid', $params['follow_userid']))
            ->orderByDesc('id');
    }
}
