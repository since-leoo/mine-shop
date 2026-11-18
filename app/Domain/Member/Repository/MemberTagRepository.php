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

use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Member\MemberTag;
use Hyperf\Database\Model\Builder;

/**
 * @extends IRepository<MemberTag>
 */
final class MemberTagRepository extends IRepository
{
    public function __construct(protected readonly MemberTag $model) {}

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(isset($params['keyword']), static function (Builder $q) use ($params) {
                $keyword = trim((string) $params['keyword']);
                $q->where('name', 'like', '%' . $keyword . '%');
            })
            ->when(isset($params['status']), static fn (Builder $q) => $q->where('status', $params['status']))
            ->orderByDesc('sort_order')
            ->orderByDesc('id');
    }

    public function existsByName(string $name, ?int $exceptId = null): bool
    {
        $query = $this->getQuery()->where('name', $name);
        if ($exceptId) {
            $query->whereKeyNot($exceptId);
        }
        return $query->exists();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function allActive(): array
    {
        return $this->getQuery()
            ->where('status', MemberTag::STATUS_ACTIVE)
            ->orderByDesc('sort_order')
            ->orderByDesc('id')
            ->get(['id', 'name', 'color', 'status'])
            ->toArray();
    }
}
