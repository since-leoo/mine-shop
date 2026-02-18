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

use App\Domain\Trade\Seckill\Contract\SeckillProductInput;
use App\Domain\Trade\Seckill\Mapper\SeckillProductMapper;
use App\Domain\Trade\Seckill\Repository\SeckillProductRepository;
use App\Domain\Trade\Seckill\Repository\SeckillSessionRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Seckill\SeckillProduct;

final class DomainSeckillProductService extends IService
{
    public function __construct(
        public readonly SeckillProductRepository $repository,
        private readonly SeckillSessionRepository $sessionRepository
    ) {}

    public function findBySessionId(int $sessionId): array
    {
        return $this->repository->findBySessionId($sessionId);
    }

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
        if (! $product) {
            throw new \RuntimeException('商品不存在');
        }
        $entity = SeckillProductMapper::fromModel($product);
        $entity->update($dto);
        $result = $this->repository->updateFromEntity($entity);
        $this->sessionRepository->updateQuantityStats((int) $product->session_id);
        return $result;
    }

    public function delete(int $id): bool
    {
        $product = $this->repository->findById($id);
        if (! $product) {
            throw new \RuntimeException('商品不存在');
        }
        $sessionId = (int) $product->session_id;
        $result = $this->repository->deleteById($id) > 0;
        $this->sessionRepository->updateQuantityStats($sessionId);
        return $result;
    }

    public function batchCreate(array $inputs): array
    {
        $results = [];
        $sessionIds = [];
        foreach ($inputs as $dto) {
            $entity = SeckillProductMapper::getNewEntity();
            $entity->create($dto);
            if ($this->repository->existsInSession($entity->getSessionId(), $entity->getProductSkuId())) {
                continue;
            }
            $results[] = $this->repository->createFromEntity($entity);
            $sessionIds[$entity->getSessionId()] = true;
        }
        foreach (array_keys($sessionIds) as $sid) {
            $this->sessionRepository->updateQuantityStats($sid);
        }
        return $results;
    }

    public function toggleStatus(int $id): bool
    {
        $product = $this->repository->findById($id);
        if (! $product) {
            throw new \RuntimeException('商品不存在');
        }
        $entity = SeckillProductMapper::fromModel($product);
        $entity->isEnabled() ? $entity->disable() : $entity->enable();
        $result = $this->repository->updateFromEntity($entity);
        $this->sessionRepository->updateQuantityStats((int) $product->session_id);
        return $result;
    }
}
