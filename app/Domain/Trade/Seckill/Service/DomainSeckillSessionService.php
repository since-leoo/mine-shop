<?php

declare(strict_types=1);

namespace App\Domain\Trade\Seckill\Service;

use App\Infrastructure\Abstract\IService;
use Hyperf\DbConnection\Db;
use App\Domain\Trade\Seckill\Contract\SeckillSessionInput;
use App\Domain\Trade\Seckill\Mapper\SeckillSessionMapper;
use App\Domain\Trade\Seckill\Repository\SeckillProductRepository;
use App\Domain\Trade\Seckill\Repository\SeckillSessionRepository;
use App\Infrastructure\Model\Seckill\SeckillSession;

final class DomainSeckillSessionService extends IService
{
    public function __construct(
        public readonly SeckillSessionRepository $repository,
        private readonly SeckillProductRepository $productRepository
    ) {}

    public function findByActivityId(int $activityId): array
    {
        return $this->repository->findByActivityId($activityId);
    }

    public function create(SeckillSessionInput $dto): SeckillSession
    {
        $entity = SeckillSessionMapper::getNewEntity();
        $entity->create($dto);
        return $this->repository->createFromEntity($entity);
    }

    public function update(SeckillSessionInput $dto): bool
    {
        $session = $this->repository->findById($dto->getId());
        if (! $session) {
            throw new \RuntimeException('场次不存在');
        }
        $entity = SeckillSessionMapper::fromModel($session);
        if (! $entity->canBeEdited()) {
            throw new \DomainException('当前场次状态不允许编辑');
        }
        $entity->update($dto);
        return $this->repository->updateFromEntity($entity);
    }

    public function delete(int $id): bool
    {
        return (bool) Db::transaction(function () use ($id) {
            $this->productRepository->getQuery()->where('session_id', $id)->delete();
            return $this->repository->deleteById($id);
        });
    }

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

    public function updateQuantityStats(int $sessionId): void
    {
        $this->repository->updateQuantityStats($sessionId);
    }

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
}
