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

use App\Domain\Seckill\Contract\SeckillProductInput;
use App\Domain\Seckill\Mapper\SeckillProductMapper;
use App\Domain\Seckill\Repository\SeckillProductRepository;
use App\Domain\Seckill\Repository\SeckillSessionRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Seckill\SeckillProduct;
use App\Interface\Admin\Dto\Seckill\SeckillProductDto;
use Hyperf\DTO\Mapper;

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
    public function create(SeckillProductInput $dto): SeckillProduct
    {
        // 1. 通过Mapper获取新实体
        $entity = SeckillProductMapper::getNewEntity();

        // 2. 调用实体的create行为方法（内部会验证秒杀价必须小于原价）
        $entity->create($dto);

        // 3. 检查商品是否已在场次中
        if ($this->repository->existsInSession($entity->getSessionId(), $entity->getProductSkuId())) {
            throw new \RuntimeException('该商品已在此场次中');
        }

        // 4. 持久化
        $product = $this->repository->createFromEntity($entity);

        // 5. 更新场次库存统计
        $this->sessionRepository->updateQuantityStats($entity->getSessionId());

        return $product;
    }

    /**
     * 更新商品配置.
     */
    public function update(SeckillProductInput $dto): bool
    {
        // 1. 通过仓储获取Model
        $product = $this->repository->findById($dto->getId());
        if (! $product) {
            throw new \RuntimeException('商品不存在');
        }

        // 2. 通过Mapper将Model转换为Entity
        $entity = SeckillProductMapper::fromModel($product);

        // 3. 调用实体的update行为方法（内部会验证秒杀价必须小于原价）
        $entity->update($dto);

        // 4. 持久化修改
        $result = $this->repository->updateFromEntity($entity);

        // 5. 更新场次库存统计
        $this->sessionRepository->updateQuantityStats((int) $product->session_id);

        return $result;
    }

    /**
     * 移除商品.
     */
    public function delete(int $id): bool
    {
        $product = $this->repository->findById($id);
        if (! $product) {
            throw new \RuntimeException('商品不存在');
        }

        $sessionId = (int) $product->session_id;
        $result = $this->repository->deleteById($id) > 0;

        // 更新场次库存统计
        $this->sessionRepository->updateQuantityStats($sessionId);

        return $result;
    }

    /**
     * 批量添加商品.
     */
    public function batchCreate(int $activityId, int $sessionId, array $products): array
    {
        $results = [];
        $sessionIds = [];

        foreach ($products as $productData) {
            // 构建DTO数据
            $dtoData = array_merge($productData, [
                'activity_id' => $activityId,
                'session_id' => $sessionId,
            ]);

            // 创建DTO
            $dto = Mapper::map($dtoData, new SeckillProductDto());

            // 通过Mapper获取新实体
            $entity = SeckillProductMapper::getNewEntity();
            $entity->create($dto);

            // 检查商品是否已在场次中
            if ($this->repository->existsInSession($entity->getSessionId(), $entity->getProductSkuId())) {
                continue; // 跳过已存在的商品
            }

            $results[] = $this->repository->createFromEntity($entity);
            $sessionIds[$entity->getSessionId()] = true;
        }

        // 更新涉及的场次库存统计
        foreach (array_keys($sessionIds) as $sid) {
            $this->sessionRepository->updateQuantityStats($sid);
        }

        return $results;
    }

    /**
     * 切换商品状态.
     */
    public function toggleStatus(int $id): bool
    {
        $product = $this->repository->findById($id);
        if (! $product) {
            throw new \RuntimeException('商品不存在');
        }

        // 通过Mapper将Model转换为Entity
        $entity = SeckillProductMapper::fromModel($product);

        // 切换状态
        if ($entity->isEnabled()) {
            $entity->disable();
        } else {
            $entity->enable();
        }

        $result = $this->repository->updateFromEntity($entity);

        // 更新场次库存统计
        $this->sessionRepository->updateQuantityStats((int) $product->session_id);

        return $result;
    }
}
