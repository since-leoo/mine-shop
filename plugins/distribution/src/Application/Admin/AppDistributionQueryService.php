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

namespace Plugin\Distribution\Application\Admin;

use Plugin\Distribution\Domain\Service\DomainDistributionQueryService;
use Plugin\Distribution\Domain\Service\DomainDistributionService;

final class AppDistributionQueryService
{
    public function __construct(private readonly DomainDistributionService $distribution, private readonly DomainDistributionQueryService $queries) {}

    public function settings(): array
    {
        return $this->distribution->settings();
    }

    public function overview(array $filters): array
    {
        return $this->queries->overview(null, $filters);
    }

    public function members(array $filters): array
    {
        return $this->queries->members(null, $filters);
    }

    public function orders(array $filters): array
    {
        return $this->queries->orders(null, $filters);
    }

    public function commissions(array $filters): array
    {
        return $this->queries->commissions(null, $filters);
    }

    public function withdrawals(array $filters): array
    {
        return $this->queries->withdrawals(null, $filters);
    }

    public function logs(array $filters): array
    {
        return $this->queries->logs($filters);
    }
}
