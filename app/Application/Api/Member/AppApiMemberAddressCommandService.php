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
use App\Domain\Member\Contract\MemberAddressInput;

final class AppApiMemberAddressCommandService
{
    public function __construct(
        private readonly DomainApiMemberAddressCommandService $commandService
    ) {}

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
