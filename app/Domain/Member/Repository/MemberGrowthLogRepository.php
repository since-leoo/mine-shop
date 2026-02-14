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
use App\Infrastructure\Model\Member\MemberGrowthLog;
use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Builder;

/**
 * @extends IRepository<MemberGrowthLog>
 */
final class MemberGrowthLogRepository extends IRepository
{
    public function __construct(protected readonly MemberGrowthLog $model) {}

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(! empty($params['member_id']), static function (Builder $q) use ($params) {
                $q->where('member_id', (int) $params['member_id']);
            })
            ->when(! empty($params['source']), static function (Builder $q) use ($params) {
                $q->where('source', $params['source']);
            })
            ->orderByDesc('id');
    }

    /**
     * 根据会员ID查询成长值变动日志.
     */
    public function findByMemberId(int $memberId): Collection
    {
        return $this->getQuery()
            ->where('member_id', $memberId)
            ->orderByDesc('id')
            ->get();
    }
}
