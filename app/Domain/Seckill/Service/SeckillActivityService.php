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

namespace App\Domain\Seckill\Service;

use App\Domain\Seckill\Entity\SeckillActivityEntity;
use App\Domain\Seckill\Repository\SeckillActivityRepository;
use App\Domain\Seckill\Repository\SeckillSessionRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Seckill\SeckillActivity;

/**
 * 秒杀活动领域服务.
 */
final class SeckillActivityService extends IService
{
    public function __construct(
        public readonly SeckillActivityRepository $repository,
        private readonly SeckillSessionRepository $sessionRepository
    ) {}

    /**
     * 创建活动.
     */
    public function create(SeckillActivityEntity $entity): SeckillActivity
    {
        return $this->repository->createFromEntity($entity);
    }

    /**
     * 更新活动.
     */
    public function update(SeckillActivityEntity $entity): bool
    {
        return $this->repository->updateFromEntity($entity);
    }

    /**
     * 删除活动.
     */
    public function delete(int $id): bool
    {
        $sessionCount = $this->sessionRepository->countByActivityId($id);
        if ($sessionCount > 0) {
            throw new \RuntimeException('该活动下还有场次，无法删除');
        }

        return $this->repository->deleteById($id) > 0;
    }

    /**
     * 切换活动状态.
     */
    public function toggleStatus(int $id): bool
    {
        $activity = $this->repository->findById($id);
        if (! $activity) {
            throw new \InvalidArgumentException('活动不存在');
        }

        $entity = new SeckillActivityEntity();
        $entity->setId($id);
        $entity->setIsEnabled(! $activity->is_enabled);

        return $this->repository->updateFromEntity($entity);
    }

    /**
     * 获取统计数据.
     */
    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }
}
