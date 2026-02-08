<?php

declare(strict_types=1);

namespace Plugin\Since\Seckill\Domain\Service;

use App\Infrastructure\Abstract\IService;
use Plugin\Since\Seckill\Domain\Contract\SeckillProductInput;
use Plugin\Since\Seckill\Domain\Mapper\SeckillProductMapper;
use Plugin\Since\Seckill\Infrastructure\Model\SeckillProduct;
use Plugin\Since\Seckill\Domain\Repository\SeckillProductRepository;
use Plugin\Since\Seckill\Domain\Repository\SeckillSessionRepository;

final class DomainSeckillProductService extends IService
{
    public function __construct(
        public readonly SeckillProductRepository $repository,
        private readonly SeckillSessionRepository $sessionRepository
    ) {}

    public function findBySessionId(int $sessionId): array { return $this->repository->findBySessionId($sessionId); }

    public function create(SeckillProductInput $dto): SeckillProduct
    {
        $entity = SeckillProductMapper::getNewEntity();
        $entity->create($dto);
        if ($this->repository->existsInSession($entity->getSessionId(), $entity->getProductSkuId())) {
            throw new \RuntimeException('该商品已在此场次中');
        }
        $product = $this->repository->createFromEntity($entity);
        $this->sessionRepository->updateQuantityStats($entity->getSessionId());
        return $product;
    }

    public function update(SeckillProductInput $dto): bool
    {
        $product = $this->repository->findById($dto->getId());
        if (!$product) { throw new \RuntimeException('商品不存在'); }
        $entity = SeckillProductMapper::fromModel($product);
        $entity->update($dto);
        $result = $this->repository->updateFromEntity($entity);
        $this->sessionRepository->updateQuantityStats((int) $product->session_id);
        return $result;
    }

    public function delete(int $id): bool
    {
        $product = $this->repository->findById($id);
        if (!$product) { throw new \RuntimeException('商品不存在'); }
        $sessionId = (int) $product->session_id;
        $result = $this->repository->deleteById($id) > 0;
        $this->sessionRepository->updateQuantityStats($sessionId);
        return $result;
    }

    public function batchCreate(array $inputs): array
    {
        $results = []; $sessionIds = [];
        foreach ($inputs as $dto) {
            $entity = SeckillProductMapper::getNewEntity();
            $entity->create($dto);
            if ($this->repository->existsInSession($entity->getSessionId(), $entity->getProductSkuId())) { continue; }
            $results[] = $this->repository->createFromEntity($entity);
            $sessionIds[$entity->getSessionId()] = true;
        }
        foreach (array_keys($sessionIds) as $sid) { $this->sessionRepository->updateQuantityStats($sid); }
        return $results;
    }

    public function toggleStatus(int $id): bool
    {
        $product = $this->repository->findById($id);
        if (!$product) { throw new \RuntimeException('商品不存在'); }
        $entity = SeckillProductMapper::fromModel($product);
        $entity->isEnabled() ? $entity->disable() : $entity->enable();
        $result = $this->repository->updateFromEntity($entity);
        $this->sessionRepository->updateQuantityStats((int) $product->session_id);
        return $result;
    }
}
