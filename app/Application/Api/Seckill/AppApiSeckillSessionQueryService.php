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

use App\Domain\Trade\Seckill\Api\Query\DomainApiSeckillQueryService;

final readonly class AppApiSeckillSessionQueryService
{
    public function __construct(private DomainApiSeckillQueryService $queryService) {}

    public function getSessionList(?int $activityId = null): array
    {
        return $this->queryService->getSessionList($activityId);
    }
}
