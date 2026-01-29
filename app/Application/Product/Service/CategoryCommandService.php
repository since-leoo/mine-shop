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

use App\Domain\Product\Entity\CategoryEntity;
use App\Domain\Product\Service\CategoryService;
use App\Infrastructure\Model\Product\Category;
use Hyperf\Cache\Annotation\CacheEvict;

/**
 * 分类命令服务：处理所有写操作.
 */
final class CategoryCommandService
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly CategoryQueryService $queryService
    ) {}

    #[CacheEvict(prefix: 'mall:category', all: true)]
    public function create(CategoryEntity $entity): Category
    {
        return $this->categoryService->create($entity);
    }

    #[CacheEvict(prefix: 'mall:category', all: true)]
    public function update(CategoryEntity $entity): bool
    {
        $category = $this->queryService->find($entity->getId());
        $category || throw new \InvalidArgumentException('分类不存在');

        return $this->categoryService->update($entity);
    }

    #[CacheEvict(prefix: 'mall:category', all: true)]
    public function delete(int $id): bool
    {
        $category = $this->queryService->find($id);
        $category || throw new \InvalidArgumentException('分类不存在');
        $category->canDelete() || throw new \RuntimeException('该分类下还有子分类，无法删除');

        return $this->categoryService->delete($id);
    }

    #[CacheEvict(prefix: 'mall:category', all: true)]
    public function move(int $categoryId, int $newParentId): bool
    {
        $categoryId <= 0 && throw new \InvalidArgumentException('分类ID不能为空');

        $category = $this->queryService->find($categoryId);
        $category || throw new \InvalidArgumentException('分类不存在');

        $newParent = $newParentId > 0 ? $this->queryService->find($newParentId) : null;
        $newParentId > 0 && ! $newParent && throw new \InvalidArgumentException('父级分类不存在');
        $newParent && $this->categoryService->isDescendant($categoryId, $newParentId) && throw new \InvalidArgumentException('不能移动到子分类下');

        return $this->categoryService->move($categoryId, $newParentId);
    }

    #[CacheEvict(prefix: 'mall:category', all: true)]
    public function updateSort(array $sortData): bool
    {
        return $this->categoryService->updateSort($sortData);
    }
}
