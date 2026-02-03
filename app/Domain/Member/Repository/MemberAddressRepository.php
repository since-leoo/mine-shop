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
use App\Infrastructure\Model\Member\MemberAddress;
use Hyperf\Database\Model\Builder;

/**
 * @extends IRepository<MemberAddress>
 */
final class MemberAddressRepository extends IRepository
{
    public function __construct(protected readonly MemberAddress $model) {}

    public function listByMember(int $memberId, int $limit = 20): array
    {
        return $this->getQuery()
            ->where('member_id', $memberId)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function findForMember(int $memberId, int $addressId): ?MemberAddress
    {
        return $this->getQuery()
            ->where('member_id', $memberId)
            ->whereKey($addressId)
            ->first();
    }

    public function createForMember(int $memberId, array $payload): MemberAddress
    {
        $payload['member_id'] = $memberId;
        return $this->model->newQuery()->create($payload);
    }

    public function updateForMember(MemberAddress $address, array $payload): bool
    {
        return $address->fill($payload)->save();
    }

    public function deleteForMember(MemberAddress $address): bool
    {
        return (bool) $address->delete();
    }

    public function unsetDefault(int $memberId): void
    {
        $this->getQuery()
            ->where('member_id', $memberId)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    public function findDefault(int $memberId): ?MemberAddress
    {
        return $this->getQuery()
            ->where('member_id', $memberId)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->first();
    }

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query;
    }
}
