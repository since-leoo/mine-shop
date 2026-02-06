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
use App\Domain\Seckill\Contract\SeckillProductInput;
use App\Domain\Seckill\Service\SeckillProductService;
use App\Infrastructure\Model\Seckill\SeckillProduct;
use Hyperf\DbConnection\Db;

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
    public function create(SeckillProductInput $dto): SeckillProduct
    {
        return Db::transaction(fn () => $this->productService->create($dto));
    }

    /**
     * 更新商品配置.
     */
    public function update(SeckillProductInput $dto): bool
    {
        $product = $this->queryService->find($dto->getId());
        if (! $product) {
            throw new \RuntimeException('商品不存在');
        }

        return Db::transaction(fn () => $this->productService->update($dto));
    }

    /**
     * 移除商品.
     */
    public function delete(int $id): bool
    {
        $product = $this->queryService->find($id);
        if (! $product) {
            throw new \RuntimeException('商品不存在');
        }

        return Db::transaction(fn () => $this->productService->delete($id));
    }

    /**
     * 批量添加商品.
     */
    public function batchCreate(int $activityId, int $sessionId, array $products): array
    {
        return Db::transaction(fn () => $this->productService->batchCreate($activityId, $sessionId, $products));
    }

    /**
     * 切换商品状态.
     */
    public function toggleStatus(int $id): bool
    {
        $product = $this->queryService->find($id);
        if (! $product) {
            throw new \RuntimeException('商品不存在');
        }

        return Db::transaction(fn () => $this->productService->toggleStatus($id));
    }
}
