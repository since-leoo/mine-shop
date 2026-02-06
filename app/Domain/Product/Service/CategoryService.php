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

use App\Domain\Product\Contract\CategoryInput;
use App\Domain\Product\Repository\CategoryRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Exception\BusinessException;
use App\Infrastructure\Model\Product\Category;
use Hyperf\Database\Model\Collection;
use Mine\Support\ResultCode;

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
            throw new BusinessException(ResultCode::FAIL, '分类不存在');
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
            throw new BusinessException(ResultCode::FAIL, '分类不存在');
        }

        if (! $category->canDelete()) {
            throw new BusinessException(ResultCode::FAIL, '该分类下还有子分类，无法删除');
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
            throw new BusinessException(ResultCode::FAIL, '分类不存在');
        }

        if ($newParentId > 0) {
            $newParent = $this->repository->findById($newParentId);
            if (! $newParent) {
                throw new BusinessException(ResultCode::FAIL, '父级分类不存在');
            }

            if ($this->isDescendant($categoryId, $newParentId)) {
                throw new BusinessException(ResultCode::FAIL, '不能移动到子分类下');
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
