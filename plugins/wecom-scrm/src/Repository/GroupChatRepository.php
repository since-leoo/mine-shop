<?php

declare(strict_types=1);

namespace Plugin\WecomScrm\Repository;

use App\Infrastructure\Abstract\IRepository;
use Hyperf\Database\Model\Builder;
use Plugin\WecomScrm\Model\WecomGroupChat;

/** @extends IRepository<WecomGroupChat> */
final class GroupChatRepository extends IRepository
{
    public function __construct(protected readonly WecomGroupChat $model) {}

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(($params['name'] ?? '') !== '', static fn (Builder $q) => $q->where('name', 'like', '%' . $params['name'] . '%'))
            ->when(isset($params['status']) && $params['status'] !== '', static fn (Builder $q) => $q->where('status', (int) $params['status']))
            ->orderByDesc('id');
    }
}
