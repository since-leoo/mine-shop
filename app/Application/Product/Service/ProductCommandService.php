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

namespace App\Application\Product\Service;

use App\Domain\Product\Entity\ProductEntity;
use App\Domain\Product\Event\ProductCreated;
use App\Domain\Product\Event\ProductDeleted;
use App\Domain\Product\Event\ProductUpdated;
use App\Domain\Product\Service\ProductService;
use App\Infrastructure\Model\Product\Product;
use Hyperf\Cache\Annotation\CacheEvict;
use Hyperf\DbConnection\Annotation\Transactional;
use Hyperf\DbConnection\Db;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * 商品命令服务：处理所有写操作.
 */
final class ProductCommandService
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly ProductQueryService $queryService,
        private readonly EventDispatcherInterface $dispatcher
    ) {}

    /**
     * 创建商品
     *
     * @param ProductEntity $entity
     * @return Product
     */
    #[Transactional]
    public function create(ProductEntity $entity): Product
    {
        $entity = Db::transaction(fn () => $this->productService->create($entity));

        $model = $this->queryService->find($entity->getId());
        if (! $model) {
            throw new \RuntimeException('创建商品失败');
        }

        $this->dispatcher->dispatch(new ProductCreated($model));
        return $model;
    }

    /**
     * 更新商品
     *
     * @param ProductEntity $entity
     * @return bool
     */
    #[Transactional]
    public function update(ProductEntity $entity): bool
    {
        Db::transaction(fn () => $this->productService->update($entity));

        $model = $this->queryService->find($entity->getId());

        if ($model) {
            $this->dispatcher->dispatch(new ProductUpdated($model));
        }

        return true;
    }

    /**
     * 删除商品
     *
     * @param int $id
     * @return bool
     */
    #[Transactional]
    public function delete(int $id): bool
    {
        $product = $this->queryService->find($id);
        if (! $product) {
            throw new \InvalidArgumentException('商品不存在');
        }

        $entity = new ProductEntity();
        $entity->setId($id);

        Db::transaction(fn () => $this->productService->remove($entity));

        $this->dispatcher->dispatch(new ProductDeleted($product));
        return true;
    }

    #[Transactional]
    public function updateSort(array $sortData): bool
    {
        return $this->productService->updateSort($sortData);
    }
}
