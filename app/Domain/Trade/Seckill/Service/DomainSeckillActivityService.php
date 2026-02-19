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

use App\Domain\Trade\Seckill\Entity\SeckillActivityEntity;
use App\Domain\Trade\Seckill\Event\SeckillActivityCreatedEvent;
use App\Domain\Trade\Seckill\Event\SeckillActivityDeletedEvent;
use App\Domain\Trade\Seckill\Event\SeckillActivityEnabledEvent;
use App\Domain\Trade\Seckill\Event\SeckillActivityStatusChangedEvent;
use App\Domain\Trade\Seckill\Event\SeckillActivityUpdatedEvent;
use App\Domain\Trade\Seckill\Mapper\SeckillActivityMapper;
use App\Domain\Trade\Seckill\Repository\SeckillActivityRepository;
use App\Domain\Trade\Seckill\Repository\SeckillSessionRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Seckill\SeckillActivity;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * 秒杀活动领域服务.
 *
 * 负责秒杀活动的核心业务逻辑，只接受实体对象。
 * DTO 到实体的转换由应用层负责。
 */
final class DomainSeckillActivityService extends IService
{
    public function __construct(
        public readonly SeckillActivityRepository $repository,
        private readonly SeckillSessionRepository $sessionRepository,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {}

    /**
     * 创建秒杀活动.
     */
    public function create(SeckillActivityEntity $entity): SeckillActivity
    {
        $activity = $this->repository->createFromEntity($entity);
        $entity->setId((int) $activity->id);
        $this->eventDispatcher->dispatch(new SeckillActivityCreatedEvent($entity, $entity->getId()));
        return $activity;
    }

    /**
     * 更新秒杀活动.
     */
    public function update(SeckillActivityEntity $entity): bool
    {
        if (! $entity->canBeEdited()) {
            throw new \DomainException('当前活动状态不允许编辑');
        }

        // 检查是否有场次即将开始（30 分钟内）
        if ($this->hasSessionWithinCacheWarmupPeriod($entity->getId())) {
            throw new \DomainException('活动下有场次即将开始（30分钟内），无法编辑');
        }

        $result = $this->repository->updateFromEntity($entity);
        if ($result) {
            $this->eventDispatcher->dispatch(new SeckillActivityUpdatedEvent($entity, $entity->getId(), []));
        }
        return $result;
    }

    /**
     * 删除秒杀活动.
     */
    public function delete(int $id): bool
    {
        $activity = $this->repository->findById($id);
        if (! $activity) {
            throw new \RuntimeException('活动不存在');
        }
        $entity = SeckillActivityMapper::fromModel($activity);
        if (! $entity->canBeDeleted()) {
            throw new \DomainException('当前活动状态不允许删除');
        }
        if ($this->sessionRepository->countByActivityId($id) > 0) {
            throw new \DomainException('该活动下还有场次，无法删除');
        }

        // 检查是否有场次即将开始（30 分钟内）
        if ($this->hasSessionWithinCacheWarmupPeriod($id)) {
            throw new \DomainException('活动下有场次即将开始（30分钟内），无法删除');
        }

        $result = $this->repository->deleteById($id) > 0;
        if ($result) {
            $this->eventDispatcher->dispatch(new SeckillActivityDeletedEvent($id));
        }
        return $result;
    }

    /**
     * 根据 ID 获取活动实体.
     */
    public function getEntity(int $id): ?SeckillActivityEntity
    {
        $model = $this->repository->findById($id);
        if (! $model) {
            return null;
        }
        return SeckillActivityMapper::fromModel($model);
    }

    /**
     * 切换活动启用状态.
     */
    public function toggleEnabled(int $id): bool
    {
        $entity = $this->getEntity($id);
        if (! $entity) {
            throw new \RuntimeException('活动不存在');
        }
        if (! $entity->isEnabled() && ! $entity->canBeEnabled()) {
            throw new \DomainException('当前活动状态不允许启用');
        }
        $entity->toggleEnabled();
        $result = $this->repository->updateFromEntity($entity);
        if ($result) {
            $this->eventDispatcher->dispatch(new SeckillActivityEnabledEvent($id, $entity->isEnabled()));
        }
        return $result;
    }

    /**
     * 取消活动.
     */
    public function cancel(int $id): bool
    {
        $entity = $this->getEntity($id);
        if (! $entity) {
            throw new \RuntimeException('活动不存在');
        }
        $oldStatus = $entity->getStatus();
        $entity->cancel();
        $result = $this->repository->updateFromEntity($entity);
        if ($result) {
            $this->eventDispatcher->dispatch(new SeckillActivityStatusChangedEvent($id, $oldStatus, $entity->getStatus()));
            $this->eventDispatcher->dispatch(new SeckillActivityEnabledEvent($id, false));
        }
        return $result;
    }

    /**
     * 启动活动.
     */
    public function start(int $id): bool
    {
        $entity = $this->getEntity($id);
        if (! $entity) {
            throw new \RuntimeException('活动不存在');
        }
        $oldStatus = $entity->getStatus();
        $entity->start();
        $result = $this->repository->updateFromEntity($entity);
        if ($result) {
            $this->eventDispatcher->dispatch(new SeckillActivityStatusChangedEvent($id, $oldStatus, $entity->getStatus()));
        }
        return $result;
    }

    /**
     * 结束活动.
     */
    public function end(int $id): bool
    {
        $entity = $this->getEntity($id);
        if (! $entity) {
            throw new \RuntimeException('活动不存在');
        }
        $oldStatus = $entity->getStatus();
        $entity->end();
        $result = $this->repository->updateFromEntity($entity);
        if ($result) {
            $this->eventDispatcher->dispatch(new SeckillActivityStatusChangedEvent($id, $oldStatus, $entity->getStatus()));
        }
        return $result;
    }

    /**
     * 获取活动统计数据.
     */
    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }

    /**
     * 检查活动下是否有场次处于缓存预热期（开始前 30 分钟内）.
     */
    private function hasSessionWithinCacheWarmupPeriod(int $activityId): bool
    {
        $sessions = $this->sessionRepository->findByActivityId($activityId);
        $now = \Carbon\Carbon::now();

        foreach ($sessions as $session) {
            $startTime = \Carbon\Carbon::parse($session->start_time);

            // 如果开始时间已过，跳过
            if ($startTime->lte($now)) {
                continue;
            }

            // 开始前 30 分钟内
            if ($startTime->diffInMinutes($now) <= 30) {
                return true;
            }
        }

        return false;
    }
}
