<?php

declare(strict_types=1);

namespace App\Application\Api\Product;

use App\Domain\Marketing\GroupBuy\Api\Query\DomainApiGroupBuyProductDetailService;

/**
 * 拼团商品详情应用服务.
 */
final class AppApiGroupBuyProductQueryService
{
    public function __construct(
        private readonly DomainApiGroupBuyProductDetailService $detailService
    ) {}

    /**
     * @return null|array{product: array, groupBuy: mixed}
     */
    public function getDetail(int $activityId, int $spuId): ?array
    {
        return $this->detailService->getDetail($activityId, $spuId);
    }
}
