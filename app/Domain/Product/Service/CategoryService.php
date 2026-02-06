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

use App\Domain\Product\Entity\CategoryEntity;
use App\Domain\Product\Repository\CategoryRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Product\Category;
use Hyperf\Database\Model\Collection;
use Hyperf\DbConnection\Db;

/**
 * 分类领域服务：封装分类相关的核心业务逻辑.
 */
final class CategoryService extends IService
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
    public function create(CategoryEntity $entity): Category
    {
        $parentId = $entity->getParentId() ?? 0;
        $level = $parentId > 0 ? $this->calculateLevel($parentId) : 1;
        $entity->setLevel($level);

        if ($entity->needsSort()) {
            $entity->applySort(Category::getNextSort($parentId));
        }

        return $this->repository->store($entity);
    }

    /**
     * 更新分类.
     */
    public function update(CategoryEntity $entity): bool
    {
        $category = $this->repository->findById($entity->getId());
        $category || throw new \InvalidArgumentException('分类不存在');

        $parentId = $entity->getParentId();
        if ($parentId !== null) {
            $entity->setLevel($parentId > 0 ? $this->calculateLevel($parentId) : 1);
        }

        return $this->repository->update($entity);
    }

    /**
     * 删除分类.
     */
    public function delete(int $id): bool
    {
        $category = $this->repository->findById($id);
        $category || throw new \InvalidArgumentException('分类不存在');
        $category->canDelete() || throw new \RuntimeException('该分类下还有子分类，无法删除');

        return $this->repository->deleteById($id) > 0;
    }

    /**
     * 移动分类.
     */
    public function move(int $categoryId, int $newParentId): bool
    {
        $category = $this->repository->findById($categoryId);
        $category || throw new \InvalidArgumentException('分类不存在');

        $newParent = $newParentId > 0 ? $this->repository->findById($newParentId) : null;
        $newParentId > 0 && ! $newParent && throw new \InvalidArgumentException('父级分类不存在');

        if ($newParent && $this->isDescendant($categoryId, $newParentId)) {
            throw new \InvalidArgumentException('不能移动到子分类下');
        }

        $newLevel = 1;
        if ($newParentId > 0) {
            $newParent = $this->repository->findById($newParentId);
            if ($newParent) {
                $newLevel = (int) $newParent->level + 1;
            }
        }

        return (bool) Db::transaction(function () use ($categoryId, $newParentId, $newLevel) {
            $entity = (new CategoryEntity())->setId($categoryId)->moveToParent($newParentId, $newLevel);
            $this->repository->update($entity);
            $this->updateChildrenLevel($categoryId, $newLevel);
            return true;
        });
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
            $entity = (new CategoryEntity())->setId((int) $item['id'])->applySort((int) $item['sort']);
            $sanitized[] = [
                'id' => $entity->getId(),
                'sort' => $entity->getSort(),
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
            $entity = (new CategoryEntity())->setId((int) $child->id)->setLevel($childLevel);
            $this->repository->update($entity);
            $this->updateChildrenLevel((int) $child->id, $childLevel);
        }
    }
}
