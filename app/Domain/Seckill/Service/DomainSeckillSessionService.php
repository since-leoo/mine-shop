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

use App\Domain\Seckill\Contract\SeckillSessionInput;
use App\Domain\Seckill\Mapper\SeckillSessionMapper;
use App\Domain\Seckill\Repository\SeckillProductRepository;
use App\Domain\Seckill\Repository\SeckillSessionRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Seckill\SeckillSession;
use Hyperf\DbConnection\Db;

/**
 * 秒杀场次领域服务.
 */
final class DomainSeckillSessionService extends IService
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
    public function create(SeckillSessionInput $dto): SeckillSession
    {
        // 1. 通过Mapper获取新实体
        $entity = SeckillSessionMapper::getNewEntity();

        // 2. 调用实体的create行为方法
        $entity->create($dto);

        // 3. 持久化
        return $this->repository->createFromEntity($entity);
    }

    /**
     * 更新场次.
     */
    public function update(SeckillSessionInput $dto): bool
    {
        // 1. 通过仓储获取Model
        $session = $this->repository->findById($dto->getId());
        if (! $session) {
            throw new \RuntimeException('场次不存在');
        }

        // 2. 通过Mapper将Model转换为Entity
        $entity = SeckillSessionMapper::fromModel($session);

        // 检查是否可以编辑
        if (! $entity->canBeEdited()) {
            throw new \DomainException('当前场次状态不允许编辑');
        }

        // 3. 调用实体的update行为方法
        $entity->update($dto);

        // 4. 持久化修改
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
        if (! $session) {
            throw new \RuntimeException('场次不存在');
        }

        // 通过Mapper将Model转换为Entity
        $entity = SeckillSessionMapper::fromModel($session);

        // 切换状态
        $entity->toggleEnabled();

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
