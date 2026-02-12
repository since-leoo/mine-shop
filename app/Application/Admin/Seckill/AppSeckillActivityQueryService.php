<?php

declare(strict_types=1);

namespace App\Application\Admin\Seckill;

use App\Domain\Trade\Seckill\Service\DomainSeckillActivityService;
use App\Infrastructure\Model\Seckill\SeckillActivity;

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
