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

namespace Plugin\Distribution\Domain\Service;

use Plugin\Distribution\Domain\Repository\DistributionQueryRepository;

final class DomainDistributionQueryService
{
    public function __construct(private readonly DistributionQueryRepository $repository) {}

    public function overview(?int $member, array $filters = []): array
    {
        return $this->repository->overview($member, $filters);
    }

    public function members(?int $member, array $filters): array
    {
        return $this->repository->members($member, $filters);
    }

    public function orders(?int $member, array $filters): array
    {
        return $this->repository->orders($member, $filters);
    }

    public function commissions(?int $member, array $filters): array
    {
        return $this->repository->commissions($member, $filters);
    }

    public function withdrawals(?int $member, array $filters): array
    {
        return $this->repository->withdrawals($member, $filters);
    }

    public function logs(array $filters): array
    {
        return $this->repository->logs($filters);
    }

    public function ledger(int $member, array $filters): array
    {
        return $this->repository->ledger($member, $filters);
    }
}
