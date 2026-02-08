<?php

declare(strict_types=1);

namespace Plugin\Since\GroupBuy\Application\Api;

use Plugin\Since\GroupBuy\Domain\Api\Query\DomainApiGroupBuyProductDetailService;

final class AppApiGroupBuyProductQueryService
{
    public function __construct(
        private readonly DomainApiGroupBuyProductDetailService $detailService
    ) {}

    /** @return null|array{product: array, groupBuy: mixed} */
    public function getDetail(int $activityId, int $spuId): ?array
    {
        return $this->detailService->getDetail($activityId, $spuId);
    }
}
