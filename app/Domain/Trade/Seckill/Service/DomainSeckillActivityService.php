<?php

declare(strict_types=1);

namespace App\Domain\Trade\Seckill\Service;

use App\Infrastructure\Abstract\IService;
use App\Domain\Trade\Seckill\Contract\SeckillActivityInput;
use App\Domain\Trade\Seckill\Entity\SeckillActivityEntity;
use App\Domain\Trade\Seckill\Event\SeckillActivityCreatedEvent;
use App\Domain\Trade\Seckill\Event\SeckillActivityDeletedEvent;
use App\Domain\Trade\Seckill\Event\SeckillActivityEnabledEvent;
use App\Domain\Trade\Seckill\Event\SeckillActivityStatusChangedEvent;
use App\Domain\Trade\Seckill\Event\SeckillActivityUpdatedEvent;
use App\Domain\Trade\Seckill\Mapper\SeckillActivityMapper;
use App\Domain\Trade\Seckill\Repository\SeckillActivityRepository;
use App\Domain\Trade\Seckill\Repository\SeckillSessionRepository;
use App\Infrastructure\Model\Seckill\SeckillActivity;
use Psr\EventDispatcher\EventDispatcherInterface;

final class DomainSeckillActivityService extends IService
{
    public function __construct(
        public readonly SeckillActivityRepository $repository,
        private readonly SeckillSessionRepository $sessionRepository,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {}

    public function create(SeckillActivityInput $dto): SeckillActivity
    {
        $entity = SeckillActivityMapper::getNewEntity();
        $entity->create($dto);
        $activity = $this->repository->createFromEntity($entity);
        $entity->setId((int) $activity->id);
        $this->eventDispatcher->dispatch(new SeckillActivityCreatedEvent($entity, $entity->getId()));
        return $activity;
    }

    public function update(SeckillActivityInput $dto): bool
    {
        $activity = $this->repository->findById($dto->getId());
        if (! $activity) {
            throw new \RuntimeException('活动不存在');
        }
        $entity = SeckillActivityMapper::fromModel($activity);
        if (! $entity->canBeEdited()) {
            throw new \DomainException('当前活动状态不允许编辑');
        }
        $oldStatus = $entity->getStatus();
        $entity->update($dto);
        $result = $this->repository->updateFromEntity($entity);
        if ($result) {
            $this->eventDispatcher->dispatch(new SeckillActivityUpdatedEvent($entity, $entity->getId(), []));
            if ($oldStatus !== $entity->getStatus()) {
                $this->eventDispatcher->dispatch(new SeckillActivityStatusChangedEvent($entity->getId(), $oldStatus, $entity->getStatus()));
            }
        }
        return $result;
    }

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
        $result = $this->repository->deleteById($id) > 0;
        if ($result) {
            $this->eventDispatcher->dispatch(new SeckillActivityDeletedEvent($id));
        }
        return $result;
    }

    public function getEntity(int $id): SeckillActivityEntity
    {
        $model = $this->repository->findById($id);
        if (! $model) {
            throw new \RuntimeException("活动不存在: ID={$id}");
        }
        return SeckillActivityMapper::fromModel($model);
    }

    public function toggleEnabled(int $id): bool
    {
        $entity = $this->getEntity($id);
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

    public function cancel(int $id): bool
    {
        $entity = $this->getEntity($id);
        $oldStatus = $entity->getStatus();
        $entity->cancel();
        $result = $this->repository->updateFromEntity($entity);
        if ($result) {
            $this->eventDispatcher->dispatch(new SeckillActivityStatusChangedEvent($id, $oldStatus, $entity->getStatus()));
            $this->eventDispatcher->dispatch(new SeckillActivityEnabledEvent($id, false));
        }
        return $result;
    }

    public function start(int $id): bool
    {
        $entity = $this->getEntity($id);
        $oldStatus = $entity->getStatus();
        $entity->start();
        $result = $this->repository->updateFromEntity($entity);
        if ($result) {
            $this->eventDispatcher->dispatch(new SeckillActivityStatusChangedEvent($id, $oldStatus, $entity->getStatus()));
        }
        return $result;
    }

    public function end(int $id): bool
    {
        $entity = $this->getEntity($id);
        $oldStatus = $entity->getStatus();
        $entity->end();
        $result = $this->repository->updateFromEntity($entity);
        if ($result) {
            $this->eventDispatcher->dispatch(new SeckillActivityStatusChangedEvent($id, $oldStatus, $entity->getStatus()));
        }
        return $result;
    }

    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }
}
