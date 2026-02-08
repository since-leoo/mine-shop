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

namespace App\Domain\Marketing\Seckill\Service;

use App\Domain\Marketing\Seckill\Contract\SeckillActivityInput;
use App\Domain\Marketing\Seckill\Entity\SeckillActivityEntity;
use App\Domain\Marketing\Seckill\Event\SeckillActivityCreatedEvent;
use App\Domain\Marketing\Seckill\Event\SeckillActivityDeletedEvent;
use App\Domain\Marketing\Seckill\Event\SeckillActivityEnabledEvent;
use App\Domain\Marketing\Seckill\Event\SeckillActivityStatusChangedEvent;
use App\Domain\Marketing\Seckill\Event\SeckillActivityUpdatedEvent;
use App\Domain\Marketing\Seckill\Mapper\SeckillActivityMapper;
use App\Domain\Marketing\Seckill\Repository\SeckillActivityRepository;
use App\Domain\Marketing\Seckill\Repository\SeckillSessionRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Seckill\SeckillActivity;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * 秒杀活动领域服务.
 */
final class DomainSeckillActivityService extends IService
{
    public function __construct(
        public readonly SeckillActivityRepository $repository,
        private readonly SeckillSessionRepository $sessionRepository,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {}

    /**
     * 创建活动.
     */
    public function create(SeckillActivityInput $dto): SeckillActivity
    {
        // 1. 通过Mapper获取新实体
        $entity = SeckillActivityMapper::getNewEntity();

        // 2. 调用实体的create行为方法
        $entity->create($dto);

        // 3. 持久化
        $activity = $this->repository->createFromEntity($entity);
        $entity->setId((int) $activity->id);

        // 4. 触发活动创建事件
        $this->eventDispatcher->dispatch(
            new SeckillActivityCreatedEvent($entity, $entity->getId())
        );

        return $activity;
    }

    /**
     * 更新活动.
     */
    public function update(SeckillActivityInput $dto): bool
    {
        // 1. 通过仓储获取Model
        $activity = $this->repository->findById($dto->getId());
        if (! $activity) {
            throw new \RuntimeException('活动不存在');
        }

        // 2. 通过Mapper将Model转换为Entity
        $entity = SeckillActivityMapper::fromModel($activity);

        // 检查是否可以编辑
        if (! $entity->canBeEdited()) {
            throw new \DomainException('当前活动状态不允许编辑');
        }

        $oldStatus = $entity->getStatus();

        // 3. 调用实体的update行为方法
        $entity->update($dto);

        // 4. 持久化修改
        $result = $this->repository->updateFromEntity($entity);

        if ($result) {
            // 触发活动更新事件
            $this->eventDispatcher->dispatch(
                new SeckillActivityUpdatedEvent($entity, $entity->getId(), [])
            );

            // 如果状态发生变化，触发状态变更事件
            if ($oldStatus !== $entity->getStatus()) {
                $this->eventDispatcher->dispatch(
                    new SeckillActivityStatusChangedEvent($entity->getId(), $oldStatus, $entity->getStatus())
                );
            }
        }

        return $result;
    }

    /**
     * 删除活动.
     */
    public function delete(int $id): bool
    {
        $activity = $this->repository->findById($id);
        if (! $activity) {
            throw new \RuntimeException('活动不存在');
        }

        // 检查是否可以删除
        $entity = SeckillActivityMapper::fromModel($activity);
        if (! $entity->canBeDeleted()) {
            throw new \DomainException('当前活动状态不允许删除');
        }

        // 检查是否有关联的场次
        $sessionCount = $this->sessionRepository->countByActivityId($id);
        if ($sessionCount > 0) {
            throw new \DomainException('该活动下还有场次，无法删除');
        }

        $result = $this->repository->deleteById($id) > 0;

        if ($result) {
            // 触发活动删除事件
            $this->eventDispatcher->dispatch(
                new SeckillActivityDeletedEvent($id)
            );
        }

        return $result;
    }

    /**
     * 获取活动实体.
     *
     * 通过ID获取Model，然后通过Mapper转换为Entity.
     * 用于需要调用实体行为方法的场景.
     */
    public function getEntity(int $id): SeckillActivityEntity
    {
        $model = $this->repository->findById($id);

        if (! $model) {
            throw new \RuntimeException("活动不存在: ID={$id}");
        }

        return SeckillActivityMapper::fromModel($model);
    }

    /**
     * 切换活动启用状态.
     */
    public function toggleEnabled(int $id): bool
    {
        $entity = $this->getEntity($id);

        // 如果要启用，检查是否可以启用
        if (! $entity->isEnabled() && ! $entity->canBeEnabled()) {
            throw new \DomainException('当前活动状态不允许启用');
        }

        $entity->toggleEnabled();
        $result = $this->repository->updateFromEntity($entity);

        if ($result) {
            // 触发启用/禁用事件
            $this->eventDispatcher->dispatch(
                new SeckillActivityEnabledEvent($id, $entity->isEnabled())
            );
        }

        return $result;
    }

    /**
     * 取消活动.
     */
    public function cancel(int $id): bool
    {
        $entity = $this->getEntity($id);
        $oldStatus = $entity->getStatus();

        $entity->cancel();
        $result = $this->repository->updateFromEntity($entity);

        if ($result) {
            // 触发状态变更事件
            $this->eventDispatcher->dispatch(
                new SeckillActivityStatusChangedEvent($id, $oldStatus, $entity->getStatus())
            );

            // 触发禁用事件
            $this->eventDispatcher->dispatch(
                new SeckillActivityEnabledEvent($id, false)
            );
        }

        return $result;
    }

    /**
     * 开始活动.
     */
    public function start(int $id): bool
    {
        $entity = $this->getEntity($id);
        $oldStatus = $entity->getStatus();

        $entity->start();
        $result = $this->repository->updateFromEntity($entity);

        if ($result) {
            // 触发状态变更事件
            $this->eventDispatcher->dispatch(
                new SeckillActivityStatusChangedEvent($id, $oldStatus, $entity->getStatus())
            );
        }

        return $result;
    }

    /**
     * 结束活动.
     */
    public function end(int $id): bool
    {
        $entity = $this->getEntity($id);
        $oldStatus = $entity->getStatus();

        $entity->end();
        $result = $this->repository->updateFromEntity($entity);

        if ($result) {
            // 触发状态变更事件
            $this->eventDispatcher->dispatch(
                new SeckillActivityStatusChangedEvent($id, $oldStatus, $entity->getStatus())
            );
        }

        return $result;
    }

    /**
     * 获取统计数据.
     */
    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }
    /**
     * 查询最新一条 active/pending 且已启用的活动.
     */

}
