<?php

declare(strict_types=1);

namespace Plugin\Since\GroupBuy\Application\Admin;

use Plugin\Since\GroupBuy\Domain\Api\Query\DomainApiGroupBuyOrderQueryService;

final class AppGroupBuyOrderQueryService
{
    public function __construct(
        private readonly DomainApiGroupBuyOrderQueryService $domainService,
    ) {}

    /**
     * 以拼团活动为维度的汇总列表（分页）.
     *
     * @return array{list: array, total: int}
     */
    public function activitySummaryPage(array $filters, int $page, int $pageSize): array
    {
        return $this->domainService->activitySummaryPage($filters, $page, $pageSize);
    }

    /**
     * 某个拼团活动下的拼团订单列表（分页）.
     *
     * @return array{list: array, total: int}
     */
    public function ordersByActivity(int $activityId, array $filters, int $page, int $pageSize): array
    {
        return $this->domainService->ordersByActivity($activityId, $filters, $page, $pageSize);
    }
}
