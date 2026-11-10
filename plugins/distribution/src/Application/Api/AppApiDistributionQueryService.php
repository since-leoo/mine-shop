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

namespace Plugin\Distribution\Application\Api;

use Plugin\Distribution\Domain\Service\DomainDistributionQueryService;
use Plugin\Distribution\Domain\Service\DomainDistributionService;

final class AppApiDistributionQueryService
{
    public function __construct(private readonly DomainDistributionService $distribution, private readonly DomainDistributionQueryService $queries) {}

    public function status(int $id): array
    {
        return $this->distribution->status($id);
    }

    public function resolve(?string $code, ?string $token): array
    {
        return $this->distribution->resolve($code, $token);
    }

    public function overview(int $id, array $filters): array
    {
        return $this->queries->overview($id, $filters);
    }

    public function team(int $id, array $filters): array
    {
        return $this->queries->members($id, $filters);
    }

    public function orders(int $id, array $filters): array
    {
        return $this->queries->orders($id, $filters);
    }

    public function commissions(int $id, array $filters): array
    {
        return $this->queries->commissions($id, $filters);
    }

    public function withdrawals(int $id, array $filters): array
    {
        return $this->queries->withdrawals($id, $filters);
    }

    public function ledger(int $id, array $filters): array
    {
        return $this->queries->ledger($id, $filters);
    }

    public function withdrawalPolicy(): array
    {
        return $this->distribution->withdrawalPolicy();
    }
}
