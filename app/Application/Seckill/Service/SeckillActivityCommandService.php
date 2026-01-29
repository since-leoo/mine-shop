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

use App\Domain\Seckill\Entity\SeckillActivityEntity;
use App\Domain\Seckill\Service\SeckillActivityService;
use App\Infrastructure\Model\Seckill\SeckillActivity;

/**
 * 秒杀活动命令服务：处理所有写操作.
 */
final class SeckillActivityCommandService
{
    public function __construct(
        private readonly SeckillActivityService $activityService,
        private readonly SeckillActivityQueryService $queryService
    ) {}

    /**
     * 创建活动.
     */
    public function create(SeckillActivityEntity $entity): SeckillActivity
    {
        return $this->activityService->create($entity);
    }

    /**
     * 更新活动.
     */
    public function update(SeckillActivityEntity $entity): bool
    {
        $activity = $this->queryService->find($entity->getId());
        $activity || throw new \InvalidArgumentException('活动不存在');

        return $this->activityService->update($entity);
    }

    /**
     * 删除活动.
     */
    public function delete(int $id): bool
    {
        $activity = $this->queryService->find($id);
        $activity || throw new \InvalidArgumentException('活动不存在');

        return $this->activityService->delete($id);
    }

    /**
     * 切换活动状态.
     */
    public function toggleStatus(int $id): bool
    {
        $activity = $this->queryService->find($id);
        $activity || throw new \InvalidArgumentException('活动不存在');

        return $this->activityService->toggleStatus($id);
    }
}
