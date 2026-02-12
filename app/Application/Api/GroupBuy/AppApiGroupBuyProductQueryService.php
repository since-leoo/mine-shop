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

namespace App\Application\Api\GroupBuy;

use App\Domain\Trade\GroupBuy\Api\Query\DomainApiGroupBuyListService;
use App\Domain\Trade\GroupBuy\Api\Query\DomainApiGroupBuyGroupsService;
use App\Domain\Trade\GroupBuy\Api\Query\DomainApiGroupBuyProductDetailService;

final class AppApiGroupBuyProductQueryService
{
    public function __construct(
        private readonly DomainApiGroupBuyProductDetailService $detailService,
        private readonly DomainApiGroupBuyListService $listService,
        private readonly DomainApiGroupBuyGroupsService $groupsService
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

    /**
     * 获取某个拼团活动正在进行中的团列表.
     *
     * @return array<int, array{group_no: string, leader_nickname: string, leader_avatar: string, joined_count: int, need_count: int, expire_time: string}>
     */
    public function getOngoingGroups(int $activityId, int $limit = 10): array
    {
        return $this->groupsService->getOngoingGroups($activityId, $limit);
    }
}
