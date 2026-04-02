<?php

declare(strict_types=1);

namespace App\Application\Api\Seckill;

use App\Domain\Trade\Seckill\Api\Query\DomainApiSeckillQueryService;

final class AppApiSeckillSessionQueryService
{
    public function __construct(private readonly DomainApiSeckillQueryService $queryService) {}

    /**
     * @return array
     */
    public function getSessionList(?int $activityId = null): array
    {
        return $this->queryService->getSessionList($activityId);
    }
}
