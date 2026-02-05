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

use App\Domain\Seckill\Service\SeckillSessionService;
use App\Infrastructure\Model\Seckill\SeckillSession;

/**
 * 秒杀场次查询服务：处理所有读操作.
 */
final class SeckillSessionQueryService
{
    public function __construct(private readonly SeckillSessionService $sessionService) {}

    /**
     * 分页查询场次.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->sessionService->page($filters, $page, $pageSize);
    }

    /**
     * 根据ID查找场次.
     */
    public function find(int $id): ?SeckillSession
    {
        /** @var null|SeckillSession $session */
        $session = $this->sessionService->findById($id);
        $session?->load(['activity', 'products']);
        return $session;
    }

    /**
     * 获取指定活动的场次列表.
     *
     * @return SeckillSession[]
     */
    public function findByActivityId(int $activityId): array
    {
        return $this->sessionService->findByActivityId($activityId);
    }
}
