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

namespace App\Domain\Trade\Seckill\Service;

use App\Domain\Trade\Seckill\Entity\SeckillSessionEntity;
use App\Domain\Trade\Seckill\Mapper\SeckillSessionMapper;
use App\Domain\Trade\Seckill\Repository\SeckillProductRepository;
use App\Domain\Trade\Seckill\Repository\SeckillSessionRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Seckill\SeckillSession;
use Hyperf\DbConnection\Db;

/**
 * 秒杀场次领域服务.
 *
 * 负责秒杀场次的核心业务逻辑，只接受实体对象。
 * DTO 到实体的转换由应用层负责。
 */
final class DomainSeckillSessionService extends IService
{
    public function __construct(
        public readonly SeckillSessionRepository $repository,
        private readonly SeckillProductRepository $productRepository
    ) {}

    /**
     * 根据活动 ID 查询场次列表.
     */
    public function findByActivityId(int $activityId): array
    {
        return $this->repository->findByActivityId($activityId);
    }

    /**
     * 创建秒杀场次.
     *
     * @param SeckillSessionEntity $entity 场次实体
     * @return SeckillSession 创建的模型
     */
    public function create(SeckillSessionEntity $entity): SeckillSession
    {
        return $this->repository->createFromEntity($entity);
    }

    /**
     * 更新秒杀场次.
     *
     * @param SeckillSessionEntity $entity 更新后的实体
     * @return bool 是否更新成功
     * @throws \DomainException 场次状态不允许编辑时抛出
     */
    public function update(SeckillSessionEntity $entity): bool
    {
        if (! $entity->canBeEdited()) {
            throw new \DomainException('当前场次状态不允许编辑（场次已开始或即将开始）');
        }
        return $this->repository->updateFromEntity($entity);
    }

    /**
     * 删除秒杀场次.
     *
     * @param int $id 场次 ID
     * @return bool 是否删除成功
     * @throws \RuntimeException 场次不存在时抛出
     * @throws \DomainException 场次状态不允许删除时抛出
     */
    public function delete(int $id): bool
    {
        $session = $this->repository->findById($id);
        if (! $session) {
            throw new \RuntimeException('场次不存在');
        }
        $entity = SeckillSessionMapper::fromModel($session);
        if (! $entity->canBeDeleted()) {
            throw new \DomainException('当前场次状态不允许删除（场次已开始、有销量或即将开始）');
        }
        return (bool) Db::transaction(function () use ($id) {
            $this->productRepository->getQuery()->where('session_id', $id)->delete();
            return $this->repository->deleteById($id);
        });
    }

    /**
     * 切换场次启用状态.
     *
     * @param int $id 场次 ID
     * @return bool 是否操作成功
     * @throws \RuntimeException 场次不存在时抛出
     */
    public function toggleStatus(int $id): bool
    {
        $session = $this->repository->findById($id);
        if (! $session) {
            throw new \RuntimeException('场次不存在');
        }
        $entity = SeckillSessionMapper::fromModel($session);
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

    /**
     * 启动场次.
     *
     * @param int $id 场次 ID
     * @return bool 是否操作成功
     * @throws \RuntimeException 场次不存在时抛出
     */
    public function start(int $id): bool
    {
        $session = $this->repository->findById($id);
        if (! $session) {
            throw new \RuntimeException("场次不存在: ID={$id}");
        }
        $entity = SeckillSessionMapper::fromModel($session);
        $entity->start();
        return $this->repository->updateFromEntity($entity);
    }

    /**
     * 结束场次.
     *
     * @param int $id 场次 ID
     * @return bool 是否操作成功
     * @throws \RuntimeException 场次不存在时抛出
     */
    public function end(int $id): bool
    {
        $session = $this->repository->findById($id);
        if (! $session) {
            throw new \RuntimeException("场次不存在: ID={$id}");
        }
        $entity = SeckillSessionMapper::fromModel($session);
        $entity->end();
        return $this->repository->updateFromEntity($entity);
    }

    /**
     * 根据 ID 获取场次实体.
     *
     * @param int $id 场次 ID
     * @return SeckillSessionEntity|null 实体或 null
     */
    public function getEntity(int $id): ?SeckillSessionEntity
    {
        $session = $this->repository->findById($id);
        if (! $session) {
            return null;
        }
        return SeckillSessionMapper::fromModel($session);
    }
}
