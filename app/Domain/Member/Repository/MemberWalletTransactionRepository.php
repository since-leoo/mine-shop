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
use App\Infrastructure\Model\Member\MemberWalletTransaction;
use Carbon\Carbon;
use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Builder;

/**
 * @extends IRepository<MemberWalletTransaction>
 */
final class MemberWalletTransactionRepository extends IRepository
{
    public function __construct(protected readonly MemberWalletTransaction $model) {}

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(! empty($params['member_id']), static function (Builder $q) use ($params) {
                $q->where('member_id', (int) $params['member_id']);
            })
            ->when(! empty($params['wallet_type']), static function (Builder $q) use ($params) {
                $q->where('wallet_type', $params['wallet_type']);
            })
            ->when(! empty($params['source']), static function (Builder $q) use ($params) {
                $q->where('source', $params['source']);
            })
            ->when(! empty($params['operator_type']), static function (Builder $q) use ($params) {
                $q->where('operator_type', $params['operator_type']);
            })
            ->when(! empty($params['start_date']), static function (Builder $q) use ($params) {
                $q->whereDate('created_at', '>=', $params['start_date']);
            })
            ->when(! empty($params['end_date']), static function (Builder $q) use ($params) {
                $q->whereDate('created_at', '<=', $params['end_date']);
            })
            ->orderByDesc('id');
    }

    /**
     * 检查指定会员是否已存在某来源的流水记录.
     *
     * @param int $memberId 会员ID
     * @param string $walletType 钱包类型（balance/points）
     * @param string $source 来源标识
     */
    public function existsByMemberAndSource(int $memberId, string $walletType, string $source): bool
    {
        return $this->getQuery()
            ->where('member_id', $memberId)
            ->where('wallet_type', $walletType)
            ->where('source', $source)
            ->exists();
    }

    public function existsByMemberSourceAndRelated(
        int $memberId,
        string $walletType,
        string $source,
        string $relatedType,
        int $relatedId
    ): bool {
        return $this->getQuery()
            ->where('member_id', $memberId)
            ->where('wallet_type', $walletType)
            ->where('source', $source)
            ->where('related_type', $relatedType)
            ->where('related_id', $relatedId)
            ->exists();
    }

    public function existsBySourceAndRelated(
        string $walletType,
        string $source,
        string $relatedType,
        int $relatedId
    ): bool {
        return $this->getQuery()
            ->where('wallet_type', $walletType)
            ->where('source', $source)
            ->where('related_type', $relatedType)
            ->where('related_id', $relatedId)
            ->exists();
    }

    /**
     * @return Collection<int, MemberWalletTransaction>
     */
    public function findExpirablePointGrantTransactions(Carbon $cutoff): Collection
    {
        return $this->getQuery()
            ->where('wallet_type', 'points')
            ->where('type', 'adjust_in')
            ->where('amount', '>', 0)
            ->whereNotIn('source', ['points_expire', 'purchase_refund'])
            ->where('created_at', '<=', $cutoff)
            ->orderBy('member_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param array<int, int> $sourceTransactionIds
     * @return array<int, int>
     */
    public function findExpiredPointSourceTransactionIds(array $sourceTransactionIds): array
    {
        if ($sourceTransactionIds === []) {
            return [];
        }

        return $this->getQuery()
            ->where('wallet_type', 'points')
            ->where('source', 'points_expire')
            ->where('related_type', 'wallet_transaction')
            ->whereIn('related_id', $sourceTransactionIds)
            ->pluck('related_id')
            ->map(static fn ($relatedId): int => (int) $relatedId)
            ->unique()
            ->values()
            ->all();
    }
}
