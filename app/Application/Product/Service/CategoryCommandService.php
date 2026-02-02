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
    ) {}

    #[CacheEvict(prefix: 'mall:category', all: true)]
    public function create(CategoryEntity $entity): Category
    {
        return $this->categoryService->create($entity);
    }

    #[CacheEvict(prefix: 'mall:category', all: true)]
    public function update(CategoryEntity $entity): bool
    {
        return $this->categoryService->update($entity);
    }

    #[CacheEvict(prefix: 'mall:category', all: true)]
    public function delete(int $id): bool
    {
        return $this->categoryService->delete($id);
    }

    #[CacheEvict(prefix: 'mall:category', all: true)]
    public function move(int $categoryId, int $newParentId): bool
    {
        return $this->categoryService->move($categoryId, $newParentId);
    }

    #[CacheEvict(prefix: 'mall:category', all: true)]
    public function updateSort(array $sortData): bool
    {
        return $this->categoryService->updateSort($sortData);
    }
}
