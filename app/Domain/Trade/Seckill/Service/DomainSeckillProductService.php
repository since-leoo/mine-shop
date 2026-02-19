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

use App\Domain\Trade\Seckill\Entity\SeckillProductEntity;
use App\Domain\Trade\Seckill\Mapper\SeckillProductMapper;
use App\Domain\Trade\Seckill\Repository\SeckillProductRepository;
use App\Domain\Trade\Seckill\Repository\SeckillSessionRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Seckill\SeckillProduct;

/**
 * 秒杀商品领域服务.
 *
 * 负责秒杀商品的核心业务逻辑，只接受实体对象。
 * DTO 到实体的转换由应用层负责。
 */
final class DomainSeckillProductService extends IService
{
    public function __construct(
        public readonly SeckillProductRepository $repository,
        private readonly SeckillSessionRepository $sessionRepository
    ) {}

    /**
     * 根据场次 ID 查询商品列表.
     */
    public function findBySessionId(int $sessionId): array
    {
        return $this->repository->findBySessionId($sessionId);
    }

    /**
     * 创建秒杀商品.
     *
     * @param SeckillProductEntity $entity 秒杀商品实体
     * @return SeckillProduct 创建的模型
     * @throws \RuntimeException 商品已存在时抛出
     * @throws \DomainException 场次即将开始时抛出
     */
    public function create(SeckillProductEntity $entity): SeckillProduct
    {
        // 检查场次是否处于缓存预热期
        $this->ensureSessionNotInCacheWarmupPeriod($entity->getSessionId());

        if ($this->repository->existsInSession($entity->getSessionId(), $entity->getProductSkuId())) {
            throw new \RuntimeException('该商品已在此场次中');
        }

        $product = $this->repository->createFromEntity($entity);
        $this->sessionRepository->updateQuantityStats($entity->getSessionId());
        return $product;
    }

    /**
     * 更新秒杀商品.
     *
     * @param SeckillProductEntity $entity 更新后的实体
     * @return bool 是否更新成功
     * @throws \DomainException 场次即将开始时抛出
     */
    public function update(SeckillProductEntity $entity): bool
    {
        // 检查场次是否处于缓存预热期
        $this->ensureSessionNotInCacheWarmupPeriod($entity->getSessionId());

        $result = $this->repository->updateFromEntity($entity);
        $this->sessionRepository->updateQuantityStats($entity->getSessionId());
        return $result;
    }

    /**
     * 删除秒杀商品.
     *
     * @param int $id 商品 ID
     * @return bool 是否删除成功
     * @throws \RuntimeException 商品不存在时抛出
     * @throws \DomainException 场次即将开始时抛出
     */
    public function delete(int $id): bool
    {
        $product = $this->repository->findById($id);
        if (! $product) {
            throw new \RuntimeException('商品不存在');
        }

        // 检查场次是否处于缓存预热期
        $this->ensureSessionNotInCacheWarmupPeriod((int) $product->session_id);

        $sessionId = (int) $product->session_id;
        $result = $this->repository->deleteById($id) > 0;
        $this->sessionRepository->updateQuantityStats($sessionId);
        return $result;
    }

    /**
     * 批量创建秒杀商品.
     *
     * @param array<SeckillProductEntity> $entities 实体数组
     * @return array<SeckillProduct> 创建的模型数组
     */
    public function batchCreate(array $entities): array
    {
        $results = [];
        $sessionIds = [];

        foreach ($entities as $entity) {
            // 检查场次是否处于缓存预热期
            $this->ensureSessionNotInCacheWarmupPeriod($entity->getSessionId());

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

    /**
     * 切换商品启用状态.
     *
     * @param int $id 商品 ID
     * @return bool 是否操作成功
     * @throws \RuntimeException 商品不存在时抛出
     * @throws \DomainException 场次即将开始时抛出
     */
    public function toggleStatus(int $id): bool
    {
        $product = $this->repository->findById($id);
        if (! $product) {
            throw new \RuntimeException('商品不存在');
        }

        // 检查场次是否处于缓存预热期
        $this->ensureSessionNotInCacheWarmupPeriod((int) $product->session_id);

        $entity = SeckillProductMapper::fromModel($product);
        $entity->isEnabled() ? $entity->disable() : $entity->enable();
        $result = $this->repository->updateFromEntity($entity);
        $this->sessionRepository->updateQuantityStats((int) $product->session_id);
        return $result;
    }

    /**
     * 根据 ID 获取商品实体.
     *
     * @param int $id 商品 ID
     * @return SeckillProductEntity|null 实体或 null
     */
    public function getEntity(int $id): ?SeckillProductEntity
    {
        $product = $this->repository->findById($id);
        if (! $product) {
            return null;
        }
        return SeckillProductMapper::fromModel($product);
    }

    /**
     * 确保场次不在缓存预热期（开始前 30 分钟内）.
     *
     * @throws \DomainException 如果场次即将开始
     */
    private function ensureSessionNotInCacheWarmupPeriod(int $sessionId): void
    {
        $session = $this->sessionRepository->findById($sessionId);
        if (! $session) {
            return;
        }

        $startTime = \Carbon\Carbon::parse($session->start_time);
        $now = \Carbon\Carbon::now();

        // 如果开始时间已过，检查状态
        if ($startTime->lte($now)) {
            if ($session->status === 'active') {
                throw new \DomainException('场次已开始，无法修改商品');
            }
            return;
        }

        // 开始前 30 分钟内禁止修改
        if ($startTime->diffInMinutes($now) <= 30) {
            throw new \DomainException('场次即将开始（30分钟内），无法修改商品');
        }
    }
}
