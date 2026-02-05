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

namespace App\Application\Seckill\Service;

use App\Domain\Seckill\Service\SeckillActivityService;
use App\Infrastructure\Model\Seckill\SeckillActivity;

/**
 * 秒杀活动查询服务：处理所有读操作.
 */
final class SeckillActivityQueryService
{
    public function __construct(private readonly SeckillActivityService $activityService) {}

    /**
     * 分页查询活动.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->activityService->page($filters, $page, $pageSize);
    }

    /**
     * 根据ID查找活动.
     */
    public function find(int $id): ?SeckillActivity
    {
        /** @var null|SeckillActivity $activity */
        $activity = $this->activityService->findById($id);
        $activity?->load(['sessions']);
        return $activity;
    }

    /**
     * 获取统计数据.
     *
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        return $this->activityService->getStatistics();
    }
}
