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

use App\Domain\Seckill\Entity\SeckillProductEntity;
use App\Domain\Seckill\Repository\SeckillProductRepository;
use App\Domain\Seckill\Repository\SeckillSessionRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Seckill\SeckillProduct;

/**
 * 秒杀商品领域服务.
 */
final class SeckillProductService extends IService
{
    public function __construct(
        public readonly SeckillProductRepository $repository,
        private readonly SeckillSessionRepository $sessionRepository
    ) {}

    /**
     * 获取指定场次的商品列表.
     */
    public function findBySessionId(int $sessionId): array
    {
        return $this->repository->findBySessionId($sessionId);
    }

    /**
     * 添加商品到场次.
     */
    public function create(SeckillProductEntity $entity): SeckillProduct
    {
        // 验证秒杀价必须小于原价
        if ($entity->getSeckillPrice() >= $entity->getOriginalPrice()) {
            throw new \InvalidArgumentException('秒杀价必须小于原价');
        }

        // 检查商品是否已在场次中
        if ($this->repository->existsInSession($entity->getSessionId(), $entity->getProductSkuId())) {
            throw new \RuntimeException('该商品已在此场次中');
        }

        $product = $this->repository->createFromEntity($entity);

        // 更新场次库存统计
        $this->sessionRepository->updateQuantityStats($entity->getSessionId());

        return $product;
    }

    /**
     * 更新商品配置.
     */
    public function update(SeckillProductEntity $entity): bool
    {
        // 验证秒杀价必须小于原价
        if ($entity->getSeckillPrice() !== null && $entity->getOriginalPrice() !== null) {
            if ($entity->getSeckillPrice() >= $entity->getOriginalPrice()) {
                throw new \InvalidArgumentException('秒杀价必须小于原价');
            }
        }

        $result = $this->repository->updateFromEntity($entity);

        // 获取原商品信息以更新场次统计
        $product = $this->repository->findById($entity->getId());
        if ($product) {
            $this->sessionRepository->updateQuantityStats((int) $product->session_id);
        }

        return $result;
    }

    /**
     * 移除商品.
     */
    public function delete(int $id): bool
    {
        $product = $this->repository->findById($id);
        if (! $product) {
            throw new \InvalidArgumentException('商品不存在');
        }

        $sessionId = (int) $product->session_id;
        $result = $this->repository->deleteById($id) > 0;

        // 更新场次库存统计
        $this->sessionRepository->updateQuantityStats($sessionId);

        return $result;
    }

    /**
     * 批量添加商品.
     *
     * @param SeckillProductEntity[] $entities
     * @return SeckillProduct[]
     */
    public function batchCreate(array $entities): array
    {
        $products = [];
        $sessionIds = [];

        foreach ($entities as $entity) {
            // 验证秒杀价必须小于原价
            if ($entity->getSeckillPrice() >= $entity->getOriginalPrice()) {
                throw new \InvalidArgumentException('秒杀价必须小于原价');
            }

            // 检查商品是否已在场次中
            if ($this->repository->existsInSession($entity->getSessionId(), $entity->getProductSkuId())) {
                continue; // 跳过已存在的商品
            }

            $products[] = $this->repository->createFromEntity($entity);
            $sessionIds[$entity->getSessionId()] = true;
        }

        // 更新涉及的场次库存统计
        foreach (array_keys($sessionIds) as $sessionId) {
            $this->sessionRepository->updateQuantityStats($sessionId);
        }

        return $products;
    }

    /**
     * 切换商品状态.
     */
    public function toggleStatus(int $id): bool
    {
        $product = $this->repository->findById($id);
        if (! $product) {
            throw new \InvalidArgumentException('商品不存在');
        }

        $entity = new SeckillProductEntity();
        $entity->setId($id);
        $entity->setIsEnabled(! $product->is_enabled);

        $result = $this->repository->updateFromEntity($entity);

        // 更新场次库存统计
        $this->sessionRepository->updateQuantityStats((int) $product->session_id);

        return $result;
    }
}
