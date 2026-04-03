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

namespace App\Domain\Trade\GroupBuy\Api\Query;

use App\Domain\Trade\GroupBuy\Repository\GroupBuyRepository;
use App\Infrastructure\Model\GroupBuy\GroupBuy;

final class DomainApiGroupBuyListService
{
    public function __construct(private readonly GroupBuyRepository $repository) {}

    /**
     * 获取进行中/即将开始的拼团活动列表（小程序促销页用）.
     *
     * @return array{list: array, statusTag: string, time: int, banner: string}
     */
    public function getPromotionList(int $limit = 20): array
    {
        $activities = $this->repository->findPromotionActivities($limit);

        if ($activities->isEmpty()) {
            return ['list' => [], 'statusTag' => 'expired', 'time' => 0, 'banner' => ''];
        }

        $list = $activities->map(static fn (GroupBuy $gb) => [
            'spuId' => (string) $gb->product_id,
            'thumb' => $gb->product->main_image ?? '',
            'title' => $gb->product->name ?? '',
            'price' => $gb->group_price,
            'originPrice' => $gb->original_price,
            'tags' => [['title' => $gb->min_people . '人团']],
            'activityId' => $gb->id,
            'minPeople' => (int) $gb->min_people,
            'soldQuantity' => $gb->sold_quantity ?? 0,
            'groupCount' => (int) ($gb->group_count ?? 0),
            'successGroupCount' => (int) ($gb->success_group_count ?? 0),
            'groupTimeLimit' => (int) ($gb->group_time_limit ?? 0),
        ])->toArray();

        // Use the first activity to build section-level status/countdown
        $first = $activities->first();
        $statusTag = $first->status === 'active' ? 'running' : 'notStart';
        $remainMs = max(0, ($first->end_time?->getTimestamp() - time()) * 1000);

        return [
            'list' => $list,
            'statusTag' => $statusTag,
            'time' => $remainMs,
            'banner' => '',
        ];
    }
}
