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

namespace App\Domain\Member\Api\Query;

use App\Domain\Member\Repository\MemberAddressRepository;
use App\Infrastructure\Abstract\IService;

/**
 * 面向 API 场景的会员地址查询领域服务.
 */
final class DomainApiMemberAddressQueryService extends IService
{
    public function __construct(public readonly MemberAddressRepository $repository) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(int $memberId, int $limit = 20): array
    {
        return $this->repository->listByMember($memberId, $limit);
    }

    /**
     * @return null|array<string, mixed>
     */
    public function detail(int $memberId, int $addressId): ?array
    {
        $address = $this->repository->findForMember($memberId, $addressId);
        return $address?->toArray();
    }
}
