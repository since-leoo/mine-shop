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

namespace Plugin\Since\GroupBuy\Application\Api;

use Plugin\Since\GroupBuy\Domain\Api\Query\DomainApiGroupBuyListService;
use Plugin\Since\GroupBuy\Domain\Api\Query\DomainApiGroupBuyProductDetailService;

final class AppApiGroupBuyProductQueryService
{
    public function __construct(
        private readonly DomainApiGroupBuyProductDetailService $detailService,
        private readonly DomainApiGroupBuyListService $listService
    ) {}

    /** @return null|array{product: array, groupBuy: mixed} */
    public function getDetail(int $activityId, int $spuId): ?array
    {
        return $this->detailService->getDetail($activityId, $spuId);
    }

    /**
     * @return array{list: array, statusTag: string, time: int}
     */
    public function getPromotionList(int $limit = 20): array
    {
        return $this->listService->getPromotionList($limit);
    }
}
