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

namespace App\Application\Admin\Trade;

use App\Domain\Trade\Order\Service\DomainSeckillOrderQueryService;

final class AppSeckillOrderQueryService
{
    public function __construct(
        private readonly DomainSeckillOrderQueryService $domainService,
    ) {}

    public function activitySummaryPage(array $filters, int $page, int $pageSize): array
    {
        return $this->domainService->activitySummaryPage($filters, $page, $pageSize);
    }

    public function ordersByActivity(int $activityId, array $filters, int $page, int $pageSize): array
    {
        return $this->domainService->ordersByActivity($activityId, $filters, $page, $pageSize);
    }
}
