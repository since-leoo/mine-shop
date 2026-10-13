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
use App\Domain\Trade\Seckill\Contract\SeckillPromotionQueryInput;
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
     * 仅编排查询，展示字段由 Interface Transformer 负责。
     */
    public function getPromotionList(SeckillPromotionQueryInput $input): ?array
    {
        return $this->queryService->getPromotionList($input);
    }
}
