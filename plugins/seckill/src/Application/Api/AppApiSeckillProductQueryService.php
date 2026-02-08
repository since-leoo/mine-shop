<?php

declare(strict_types=1);

namespace Plugin\Since\Seckill\Application\Api;

use Plugin\Since\Seckill\Domain\Api\Query\DomainApiSeckillProductDetailService;

final class AppApiSeckillProductQueryService
{
    public function __construct(private readonly DomainApiSeckillProductDetailService $detailService) {}

    public function getDetail(int $sessionId, int $spuId): ?array
    {
        return $this->detailService->getDetail($sessionId, $spuId);
    }
}
