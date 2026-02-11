<?php

declare(strict_types=1);

namespace Plugin\Since\Seckill\Application\Admin;

use Plugin\Since\Seckill\Domain\Api\Query\DomainApiSeckillOrderQueryService;

final class AppSeckillOrderQueryService
{
    public function __construct(
        private readonly DomainApiSeckillOrderQueryService $domainService,
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
