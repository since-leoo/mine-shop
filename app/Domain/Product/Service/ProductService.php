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

namespace App\Domain\Product\Service;

use App\Domain\Product\Entity\ProductEntity;
use App\Domain\Product\Repository\ProductRepository;
use App\Domain\SystemSetting\Service\MallSettingService;
use App\Infrastructure\Model\Product\Product;

/**
 * 商品领域服务：封装商品相关的核心业务逻辑.
 */
final class ProductService
{
    public function __construct(
        private readonly ProductRepository $repository,
        private readonly MallSettingService $mallSettingService,
    ) {}

    /**
     * 分页查询商品.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->repository->page($filters, $page, $pageSize);
    }

    /**
     * 创建商品.
     */
    public function create(ProductEntity $entity): ProductEntity
    {
        $this->applyProductSettings($entity);
        $entity->ensureCanPersist(true);
        $entity->syncPriceRange();
        return $this->repository->save($entity);
    }

    /**
     * 更新商品.
     */
    public function update(ProductEntity $entity): void
    {
        $this->applyProductSettings($entity);
        $entity->ensureCanPersist();
        $entity->syncPriceRange();
        $this->repository->update($entity);
    }

    /**
     * 删除商品.
     */
    public function remove(ProductEntity $entity): void
    {
        $this->repository->remove($entity);
    }

    /**
     * 批量更新排序.
     */
    public function updateSort(array $sortData): bool
    {
        foreach ($sortData as $item) {
            if (isset($item['id'], $item['sort'])) {
                $entity = new ProductEntity();
                $entity->setId((int) $item['id']);
                $entity->applySort((int) $item['sort']);
                $this->repository->updateById($entity->getId(), ['sort' => $entity->getSort()]);
            }
        }
        return true;
    }

    /**
     * 根据ID获取商品信息.
     */
    public function getInfoById(int $id): ?Product
    {
        /** @var null|Product $product */
        $product = $this->repository->findById($id);

        $product?->load(['category', 'brand', 'skus', 'attributes', 'gallery']);

        return $product;
    }

    private function applyProductSettings(ProductEntity $entity): void
    {
        $entity->applySettingConstraints(
            $this->mallSettingService->product(),
            $this->mallSettingService->content()
        );
    }
}
