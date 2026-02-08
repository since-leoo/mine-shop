<?php

declare(strict_types=1);

namespace Plugin\Since\Seckill\Domain\Api\Query;

use Plugin\Since\Seckill\Infrastructure\Model\SeckillProduct;
use Plugin\Since\Seckill\Domain\Repository\SeckillActivityRepository;
use Plugin\Since\Seckill\Domain\Repository\SeckillProductRepository;
use Plugin\Since\Seckill\Domain\Repository\SeckillSessionRepository;

final class DomainApiSeckillQueryService
{
    public function __construct(
        private readonly SeckillActivityRepository $activityRepository,
        private readonly SeckillSessionRepository $sessionRepository,
        private readonly SeckillProductRepository $productRepository
    ) {}

    public function getHomeSeckill(int $productLimit = 6): array
    {
        $empty = ['list' => [], 'endTime' => null, 'title' => '', 'activityId' => null, 'sessionId' => null];
        $activity = $this->activityRepository->findLatestEnabledActiveOrPending();
        if (!$activity) { return $empty; }
        $session = $this->sessionRepository->findNearestEnabledActiveOrPending($activity->id);
        if (!$session) { return $empty; }
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
