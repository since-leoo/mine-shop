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

namespace App\Application\Commad;

use App\Application\Query\SeckillProductQueryService;
use App\Domain\Seckill\Entity\SeckillProductEntity;
use App\Domain\Seckill\Service\SeckillProductService;
use App\Infrastructure\Model\Seckill\SeckillProduct;

/**
 * 秒杀商品命令服务：处理所有写操作.
 */
final class SeckillProductCommandService
{
    public function __construct(
        private readonly SeckillProductService $productService,
        private readonly SeckillProductQueryService $queryService
    ) {}

    /**
     * 添加商品到场次.
     */
    public function create(SeckillProductEntity $entity): SeckillProduct
    {
        return $this->productService->create($entity);
    }

    /**
     * 更新商品配置.
     */
    public function update(SeckillProductEntity $entity): bool
    {
        $product = $this->queryService->find($entity->getId());
        $product || throw new \InvalidArgumentException('商品不存在');

        return $this->productService->update($entity);
    }

    /**
     * 移除商品.
     */
    public function delete(int $id): bool
    {
        $product = $this->queryService->find($id);
        $product || throw new \InvalidArgumentException('商品不存在');

        return $this->productService->delete($id);
    }

    /**
     * 批量添加商品.
     *
     * @param SeckillProductEntity[] $entities
     * @return SeckillProduct[]
     */
    public function batchCreate(array $entities): array
    {
        return $this->productService->batchCreate($entities);
    }

    /**
     * 切换商品状态.
     */
    public function toggleStatus(int $id): bool
    {
        $product = $this->queryService->find($id);
        $product || throw new \InvalidArgumentException('商品不存在');

        return $this->productService->toggleStatus($id);
    }
}
