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

namespace Plugin\Since\Seckill\Application\Admin;

use Plugin\Since\Seckill\Domain\Service\DomainSeckillActivityService;
use Plugin\Since\Seckill\Infrastructure\Model\SeckillActivity;

final class AppSeckillActivityQueryService
{
    public function __construct(private readonly DomainSeckillActivityService $activityService) {}

    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->activityService->page($filters, $page, $pageSize);
    }

    public function find(int $id): ?SeckillActivity
    {
        /** @var null|SeckillActivity $activity */
        $activity = $this->activityService->findById($id);
        $activity?->load(['sessions']);
        return $activity;
    }

    public function stats(): array
    {
        return $this->activityService->getStatistics();
    }
}
