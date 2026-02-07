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

namespace App\Domain\Member\Service;

use App\Domain\Member\Repository\MemberAddressRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;

/**
 * 会员地址领域服务（公共/后台管理端）.
 *
 * API 端专属写操作（create、update、delete、setDefault）已迁移至
 * Domain\Member\Api\Command\MemberAddressCommandApiService.
 */
final class DomainMemberAddressService extends IService
{
    public function __construct(public readonly MemberAddressRepository $repository) {}

    public function list(int $memberId, int $limit = 20): array
    {
        return $this->repository->listByMember($memberId, $limit);
    }

    public function detail(int $memberId, int $addressId): array
    {
        $address = $this->repository->findForMember($memberId, $addressId);
        if ($address === null) {
            throw new BusinessException(ResultCode::NOT_FOUND, '地址不存在');
        }

        return $address->toArray();
    }

    public function default(int $memberId): ?array
    {
        $address = $this->repository->findDefault($memberId);
        return $address?->toArray();
    }
}
