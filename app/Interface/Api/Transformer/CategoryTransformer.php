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

namespace App\Interface\Api\Transformer;

use App\Infrastructure\Model\Product\Category;

final class CategoryTransformer
{
    /**
     * @param iterable<Category> $categories
     * @return array<int, array<string, mixed>>
     */
    public function transformTree(iterable $categories): array
    {
        $result = [];
        foreach ($categories as $category) {
            $result[] = $this->transformNode($category);
        }
        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformNode(Category $category): array
    {
        $children = $this->resolveChildren($category);

        return [
            'categoryId' => (string) $category->id,
            'parentId' => (string) $category->parent_id,
            'title' => $category->name,
            'icon' => $category->icon,
            'thumbnail' => $this->resolveThumbnail($category),
            'level' => (int) $category->level,
            'children' => $children === []
                ? []
                : $this->transformTree($children),
        ];
    }

    /**
     * @return Category[]
     */
    private function resolveChildren(Category $category): array
    {
        if ($category->relationLoaded('children')) {
            return $category->children->all();
        }

        if ($category->relationLoaded('allChildren')) {
            return $category->allChildren->all();
        }

        return [];
    }

    private function resolveThumbnail(Category $category): ?string
    {
        return $category->thumbnail ?: $category->icon;
    }
}
