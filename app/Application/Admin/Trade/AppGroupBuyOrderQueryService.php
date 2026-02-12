<?php

declare(strict_types=1);

namespace App\Application\Admin\Trade;

use App\Domain\Trade\Order\Service\DomainGroupBuyOrderQueryService;

final class AppGroupBuyOrderQueryService
{
    public function __construct(
        private readonly DomainGroupBuyOrderQueryService $domainService,
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
