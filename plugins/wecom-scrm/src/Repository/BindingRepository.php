<?php

declare(strict_types=1);

namespace Plugin\WecomScrm\Repository;

use App\Infrastructure\Abstract\IRepository;
use Hyperf\Database\Model\Builder;
use Plugin\WecomScrm\Model\WecomBinding;

/** @extends IRepository<WecomBinding> */
final class BindingRepository extends IRepository
{
    public function __construct(protected readonly WecomBinding $model) {}

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(($params['external_userid'] ?? '') !== '', static fn (Builder $q) => $q->where('external_userid', $params['external_userid']))
            ->orderByDesc('id');
    }
}
