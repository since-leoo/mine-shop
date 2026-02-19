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

namespace App\Application\Admin\Seckill;

use App\Domain\Trade\Seckill\Contract\SeckillProductInput;
use App\Domain\Trade\Seckill\Mapper\SeckillProductMapper;
use App\Domain\Trade\Seckill\Service\DomainSeckillProductService;
use App\Infrastructure\Model\Seckill\SeckillProduct;
use App\Interface\Admin\Dto\Seckill\SeckillProductDto;
use Hyperf\DbConnection\Db;
use Hyperf\DTO\Mapper;

/**
 * 秒杀商品应用服务.
 *
 * 负责 DTO 到实体的转换，协调领域服务完成用例。
 */
final class AppSeckillProductCommandService
{
    public function __construct(
        private readonly DomainSeckillProductService $productService,
        private readonly AppSeckillProductQueryService $queryService
    ) {}

    /**
     * 创建秒杀商品.
     */
    public function create(SeckillProductInput $dto): SeckillProduct
    {
        $entity = SeckillProductMapper::fromDto($dto);
        return Db::transaction(fn () => $this->productService->create($entity));
    }

    /**
     * 更新秒杀商品.
     */
    public function update(SeckillProductInput $dto): bool
    {
        $entity = $this->productService->getEntity($dto->getId());
        if (! $entity) {
            throw new \RuntimeException('商品不存在');
        }
        $entity->update($dto);
        return Db::transaction(fn () => $this->productService->update($entity));
    }

    /**
     * 删除秒杀商品.
     */
    public function delete(int $id): bool
    {
        if (! $this->queryService->find($id)) {
            throw new \RuntimeException('商品不存在');
        }
        return Db::transaction(fn () => $this->productService->delete($id));
    }

    /**
     * 批量创建秒杀商品.
     */
    public function batchCreate(int $activityId, int $sessionId, array $products): array
    {
        $entities = array_map(static function (array $productData) use ($activityId, $sessionId) {
            $dto = Mapper::map(array_merge($productData, ['activity_id' => $activityId, 'session_id' => $sessionId]), new SeckillProductDto());
            return SeckillProductMapper::fromDto($dto);
        }, $products);

        return Db::transaction(fn () => $this->productService->batchCreate($entities));
    }

    /**
     * 切换商品启用状态.
     */
    public function toggleStatus(int $id): bool
    {
        if (! $this->queryService->find($id)) {
            throw new \RuntimeException('商品不存在');
        }
        return Db::transaction(fn () => $this->productService->toggleStatus($id));
    }
}
