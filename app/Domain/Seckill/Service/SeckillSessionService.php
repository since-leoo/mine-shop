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

use App\Domain\Seckill\Entity\SeckillSessionEntity;
use App\Domain\Seckill\Repository\SeckillProductRepository;
use App\Domain\Seckill\Repository\SeckillSessionRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Seckill\SeckillSession;
use Hyperf\DbConnection\Db;

/**
 * 秒杀场次领域服务.
 */
final class SeckillSessionService extends IService
{
    public function __construct(
        public readonly SeckillSessionRepository $repository,
        private readonly SeckillProductRepository $productRepository
    ) {}

    /**
     * 获取指定活动的场次列表.
     */
    public function findByActivityId(int $activityId): array
    {
        return $this->repository->findByActivityId($activityId);
    }

    /**
     * 创建场次.
     */
    public function create(SeckillSessionEntity $entity): SeckillSession
    {
        return $this->repository->createFromEntity($entity);
    }

    /**
     * 更新场次.
     */
    public function update(SeckillSessionEntity $entity): bool
    {
        return $this->repository->updateFromEntity($entity);
    }

    /**
     * 删除场次.
     */
    public function delete(int $id): bool
    {
        return (bool) Db::transaction(function () use ($id) {
            // 级联删除场次下的商品
            $this->productRepository->getQuery()->where('session_id', $id)->delete();
            return $this->repository->deleteById($id);
        });
    }

    /**
     * 切换场次状态.
     */
    public function toggleStatus(int $id): bool
    {
        $session = $this->repository->findById($id);

        $entity = new SeckillSessionEntity();
        $entity->setId($id);
        $entity->setIsEnabled(! $session->is_enabled);

        return $this->repository->updateFromEntity($entity);
    }

    /**
     * 更新场次库存统计.
     */
    public function updateQuantityStats(int $sessionId): void
    {
        $this->repository->updateQuantityStats($sessionId);
    }
}
