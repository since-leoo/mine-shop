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

namespace App\Application\Api\Member;

use App\Domain\Member\Api\Command\DomainApiMemberAddressCommandService;
use App\Domain\Member\Api\Query\DomainApiMemberAddressQueryService;
use App\Domain\Member\Contract\MemberAddressInput;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;

final class AppApiMemberAddressQueryService
{
    public function __construct(
        private readonly DomainApiMemberAddressQueryService $queryService,
        private readonly DomainApiMemberAddressCommandService $commandService
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(int $memberId, int $limit = 20): array
    {
        return $this->queryService->list($memberId, $limit);
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(int $memberId, int $addressId): array
    {
        $address = $this->queryService->detail($memberId, $addressId);
        if ($address === null) {
            throw new BusinessException(ResultCode::NOT_FOUND, '地址不存在');
        }

        return $address;
    }

    /**
     * @return array<string, mixed>
     */
    public function create(int $memberId, MemberAddressInput $input): array
    {
        return $this->commandService->create($memberId, $input);
    }

    /**
     * @return array<string, mixed>
     */
    public function update(int $memberId, int $addressId, MemberAddressInput $input): array
    {
        return $this->commandService->update($memberId, $addressId, $input);
    }

    public function delete(int $memberId, int $addressId): void
    {
        $this->commandService->delete($memberId, $addressId);
    }

    public function setDefault(int $memberId, int $addressId): void
    {
        $this->commandService->setDefault($memberId, $addressId);
    }
}
