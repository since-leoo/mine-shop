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

use App\Domain\Product\Entity\BrandEntity;
use App\Domain\Product\Repository\BrandRepository;
use App\Infrastructure\Model\Product\Brand;

/**
 * 品牌领域服务：封装品牌相关的核心业务逻辑.
 */
final class BrandService
{
    public function __construct(private readonly BrandRepository $repository) {}

    /**
     * 分页查询品牌.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->repository->page($filters, $page, $pageSize);
    }

    /**
     * 根据ID查找品牌.
     */
    public function findById(int $id): ?Brand
    {
        return $this->repository->findById($id);
    }

    /**
     * 获取品牌选项.
     */
    public function getOptions(): array
    {
        return $this->repository->getOptions();
    }

    /**
     * 创建品牌.
     */
    public function create(BrandEntity $entity): Brand
    {
        if ($entity->needsSort()) {
            $entity->applySort(Brand::getNextSort());
        }
        $entity->ensureCanPersist(true);
        return $this->repository->store($entity);
    }

    /**
     * 更新品牌.
     */
    public function update(BrandEntity $entity): bool
    {
        $brand = $this->repository->findById($entity->getId());
        $brand || throw new \InvalidArgumentException('品牌不存在');
        $entity->ensureCanPersist();
        return $this->repository->update($entity);
    }

    /**
     * 删除品牌.
     */
    public function delete(int $id): bool
    {
        /** @var null|Brand $brand */
        $brand = $this->repository->findById($id);
        $brand || throw new \InvalidArgumentException('品牌不存在');
        $brand->canDelete() || throw new \RuntimeException('该品牌下还有商品，无法删除');

        return $this->repository->deleteById($id) > 0;
    }

    /**
     * 批量更新排序.
     */
    public function updateSort(array $sortData): bool
    {
        $sanitized = [];
        foreach ($sortData as $item) {
            if (! isset($item['id'], $item['sort'])) {
                continue;
            }
            $entity = (new BrandEntity())
                ->setId((int) $item['id'])
                ->applySort((int) $item['sort']);
            $sanitized[] = [
                'id' => $entity->getId(),
                'sort' => $entity->getSort(),
            ];
        }

        return $sanitized === [] ? true : $this->repository->updateSort($sanitized);
    }
}
