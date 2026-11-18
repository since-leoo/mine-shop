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

namespace App\Domain\Catalog\Category\Service;

use App\Domain\Catalog\Category\Contract\CategoryInput;

use App\Domain\Catalog\Category\Repository\CategoryRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Error\CatalogError;
use App\Infrastructure\Model\Product\Category;
use Hyperf\Database\Model\Collection;

/**
 * 分类领域服务：封装分类相关的核心业务逻辑.
 *
 * @extends IService<\App\Infrastructure\Model\Product\Category>
 */
final class DomainCategoryService extends IService
{
    public function __construct(public readonly CategoryRepository $repository) {}

    /**
     * 获取分类树结构.
     */
    public function getTree(int $parentId = 0): Collection
    {
        return $this->repository->getTree($parentId);
    }

    /**
     * 获取分类选项.
     */
    public function getOptions(int $excludeId = 0): array
    {
        return $this->repository->getOptions($excludeId);
    }

    /**
     * 创建分类.
     */
    public function create(CategoryInput $input): Category
    {
        $data = $input->toArray();

        $parentId = $input->getParentId();
        $level = $parentId > 0 ? $this->calculateLevel($parentId) : 1;
        $data['level'] = $level;

        // 如果没有指定排序，使用下一个排序值
        if (! isset($data['sort']) || $data['sort'] === 0) {
            $data['sort'] = Category::getNextSort($parentId);
        }

        return $this->repository->create($data);
    }

    /**
     * 更新分类.
     */
    public function update(CategoryInput $input): bool
    {
        $id = $input->getId();
        $category = $this->repository->findById($id);

        if (! $category) {
            throw CatalogError::CATEGORY_NOT_FOUND->exception();
        }

        $data = $input->toArray();

        // 如果更新了父级分类，重新计算层级
        if (isset($data['parent_id'])) {
            $parentId = (int) $data['parent_id'];
            $data['level'] = $parentId > 0 ? $this->calculateLevel($parentId) : 1;
        }

        return $this->repository->updateById($id, $data);
    }

    /**
     * 删除分类.
     */
    public function delete(int $id): bool
    {
        $category = $this->repository->findById($id);

        if (! $category) {
            throw CatalogError::CATEGORY_NOT_FOUND->exception();
        }

        if (! $category->canDelete()) {
            throw CatalogError::CATEGORY_HAS_CHILDREN->exception();
        }

        return $this->repository->deleteById($id) > 0;
    }

    /**
     * 移动分类.
     */
    public function move(int $categoryId, int $newParentId): bool
    {
        $category = $this->repository->findById($categoryId);

        if (! $category) {
            throw CatalogError::CATEGORY_NOT_FOUND->exception();
        }

        if ($newParentId > 0) {
            $newParent = $this->repository->findById($newParentId);
            if (! $newParent) {
                throw CatalogError::CATEGORY_PARENT_NOT_FOUND->exception();
            }

            if ($this->isDescendant($categoryId, $newParentId)) {
                throw CatalogError::CATEGORY_MOVE_TO_DESCENDANT->exception();
            }
        }

        $newLevel = 1;
        if ($newParentId > 0) {
            $newParent = $this->repository->findById($newParentId);
            if ($newParent) {
                $newLevel = (int) $newParent->level + 1;
            }
        }

        $this->repository->updateById($categoryId, [
            'parent_id' => $newParentId,
            'level' => $newLevel,
        ]);

        $this->updateChildrenLevel($categoryId, $newLevel);

        return true;
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
            $sanitized[] = [
                'id' => (int) $item['id'],
                'sort' => (int) $item['sort'],
            ];
        }

        return $sanitized === [] || $this->repository->updateSort($sanitized);
    }

    /**
     * 检查是否为后代分类.
     */
    public function isDescendant(int $ancestorId, int $descendantId): bool
    {
        $category = $this->repository->findById($descendantId);
        while ($category && $category->parent_id > 0) {
            if ((int) $category->parent_id === $ancestorId) {
                return true;
            }
            $category = $this->repository->findById((int) $category->parent_id);
        }
        return false;
    }

    /**
     * 计算层级.
     */
    private function calculateLevel(int $parentId): int
    {
        if ($parentId <= 0) {
            return 1;
        }
        $parent = $this->repository->findById($parentId);
        return $parent ? ((int) $parent->level + 1) : 1;
    }

    /**
     * 更新子分类层级.
     */
    private function updateChildrenLevel(int $parentId, int $parentLevel): void
    {
        $children = $this->repository->getTree($parentId);
        foreach ($children as $child) {
            $childLevel = $parentLevel + 1;
            $this->repository->updateById((int) $child->id, ['level' => $childLevel]);
            $this->updateChildrenLevel((int) $child->id, $childLevel);
        }
    }
}
