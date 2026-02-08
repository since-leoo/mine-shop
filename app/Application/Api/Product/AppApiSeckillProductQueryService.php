<?php

declare(strict_types=1);

namespace App\Application\Api\Product;

use App\Domain\Marketing\Seckill\Api\Query\DomainApiSeckillProductDetailService;

/**
 * 秒杀商品详情应用服务.
 */
final class AppApiSeckillProductQueryService
{
    public function __construct(
        private readonly DomainApiSeckillProductDetailService $detailService
    ) {}

    /**
     * @return null|array{product: array, seckillProduct: mixed, session: mixed}
     */
    public function getDetail(int $sessionId, int $spuId): ?array
    {
        return $this->detailService->getDetail($sessionId, $spuId);
    }
}
