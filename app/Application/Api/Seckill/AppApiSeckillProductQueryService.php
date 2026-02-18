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

namespace App\Application\Api\Seckill;

use App\Domain\Trade\Seckill\Api\Query\DomainApiSeckillProductDetailService;
use App\Domain\Trade\Seckill\Api\Query\DomainApiSeckillQueryService;

final class AppApiSeckillProductQueryService
{
    public function __construct(
        private readonly DomainApiSeckillProductDetailService $detailService,
        private readonly DomainApiSeckillQueryService $queryService
    ) {}

    public function getDetail(int $sessionId, int $spuId): ?array
    {
        return $this->detailService->getDetail($sessionId, $spuId);
    }

    /**
     * @return array{list: array, endTime: ?string, title: string, statusTag: string, time: int}
     */
    public function getPromotionList(int $limit = 20): array
    {
        return $this->queryService->getPromotionList($limit);
    }
}
