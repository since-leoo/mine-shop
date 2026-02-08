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

namespace App\Domain\Marketing\Seckill\Api\Query;

use App\Domain\Marketing\Seckill\Repository\SeckillActivityRepository;
use App\Domain\Marketing\Seckill\Repository\SeckillProductRepository;
use App\Domain\Marketing\Seckill\Repository\SeckillSessionRepository;
use App\Infrastructure\Model\Seckill\SeckillProduct;

/**
 * 面向 API 场景的秒杀查询领域服务.
 */
final class DomainApiSeckillQueryService
{
    public function __construct(
        private readonly SeckillActivityRepository $activityRepository,
        private readonly SeckillSessionRepository $sessionRepository,
        private readonly SeckillProductRepository $productRepository
    ) {}

    /**
     * 获取首页秒杀展示数据（最新一条活动 + 最近场次 + 商品列表）.
     *
     * @return array{list: array, endTime: string|null, title: string, activityId: int|null, sessionId: int|null}
     */
    public function getHomeSeckill(int $productLimit = 6): array
    {
        $empty = ['list' => [], 'endTime' => null, 'title' => '', 'activityId' => null, 'sessionId' => null];

        $activity = $this->activityRepository->findLatestEnabledActiveOrPending();
        if (! $activity) {
            return $empty;
        }

        $session = $this->sessionRepository->findNearestEnabledActiveOrPending($activity->id);
        if (! $session) {
            return $empty;
        }

        $seckillProducts = $this->productRepository->findEnabledBySessionIdWithProduct($session->id, $productLimit);

        $list = array_map(static fn (SeckillProduct $sp) => [
            'spuId' => (string) $sp->product_id,
            'skuId' => (string) $sp->product_sku_id,
            'thumb' => $sp->product->main_image ?? null,
            'title' => $sp->product->name ?? '',
            'price' => $sp->seckill_price,
            'originPrice' => $sp->original_price,
            'stock' => $sp->quantity - $sp->sold_quantity,
        ], $seckillProducts);

        return [
            'list' => $list,
            'endTime' => $session->end_time?->toDateTimeString(),
            'title' => $activity->title ?: '限时秒杀',
            'activityId' => $activity->id,
            'sessionId' => $session->id,
        ];
    }
}
