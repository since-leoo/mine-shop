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
}
