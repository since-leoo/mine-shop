<?php

declare(strict_types=1);

namespace App\Domain\Trade\Seckill\Api\Query;

use App\Domain\Trade\Seckill\Repository\SeckillActivityRepository;
use App\Domain\Trade\Seckill\Repository\SeckillProductRepository;
use App\Domain\Trade\Seckill\Repository\SeckillSessionRepository;
use App\Infrastructure\Model\Seckill\SeckillProduct;

final class DomainApiSeckillQueryService
{
    public function __construct(
        private readonly SeckillActivityRepository $activityRepository,
        private readonly SeckillSessionRepository $sessionRepository,
        private readonly SeckillProductRepository $productRepository
    ) {}

    /**
     * 获取当前秒杀场次的商品列表（小程序促销页用）.
     *
     * @return array{list: array, endTime: ?string, title: string, activityId: ?int, sessionId: ?int, statusTag: string, time: int}
     */
    public function getPromotionList(int $limit = 20): array
    {
        $empty = ['list' => [], 'endTime' => null, 'title' => '', 'activityId' => null, 'sessionId' => null, 'statusTag' => 'expired', 'time' => 0, 'banner' => ''];
        $activity = $this->activityRepository->findLatestEnabledActiveOrPending();
        if (! $activity) {
            return $empty;
        }
        $session = $this->sessionRepository->findNearestEnabledActiveOrPending($activity->id);
        if (! $session) {
            return $empty;
        }
        $seckillProducts = $this->productRepository->findEnabledBySessionIdWithProduct($session->id, $limit);

        $list = array_map(static fn (SeckillProduct $sp) => [
            'spuId' => (string) $sp->product_id,
            'thumb' => $sp->product->main_image ?? '',
            'title' => $sp->product->name ?? '',
            'price' => $sp->seckill_price,
            'originPrice' => $sp->original_price,
            'tags' => [],
        ], $seckillProducts);

        $status = $session->getDynamicStatus();
        $statusTag = match ($status->value) {
            'active' => 'running',
            'pending' => 'notStart',
            default => 'expired',
        };
        $remainMs = max(0, ($session->end_time?->getTimestamp() - time()) * 1000);

        return [
            'list' => $list,
            'endTime' => $session->end_time?->toDateTimeString(),
            'title' => $activity->title ?: '限时秒杀',
            'activityId' => $activity->id,
            'sessionId' => $session->id,
            'statusTag' => $statusTag,
            'time' => $remainMs,
            'banner' => '',
        ];
    }

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
            'spuId' => (string) $sp->product_id, 'skuId' => (string) $sp->product_sku_id,
            'thumb' => $sp->product->main_image ?? null, 'title' => $sp->product->name ?? '',
            'price' => $sp->seckill_price, 'originPrice' => $sp->original_price,
            'stock' => $sp->quantity - $sp->sold_quantity,
        ], $seckillProducts);

        return ['list' => $list, 'endTime' => $session->end_time?->toDateTimeString(), 'title' => $activity->title ?: '限时秒杀', 'activityId' => $activity->id, 'sessionId' => $session->id];
    }
}
