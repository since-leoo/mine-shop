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

namespace App\Application\Query;

use App\Domain\Product\Enum\CategoryStatus;
use App\Domain\Product\Service\DomainCategoryService;
use App\Infrastructure\Model\Product\Category;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Collection\Arr;

/**
 * 分类查询服务：处理所有读操作.
 */
final class AppCategoryQueryService
{
    public function __construct(private readonly DomainCategoryService $categoryService) {}

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->categoryService->page($filters, $page, $pageSize);
    }

    public function tree(int $parentId = 0): array
    {
        return $this->convertTreeToArray($this->categoryService->getTree($parentId));
    }

    #[Cacheable(prefix: 'mall:category:detail', ttl: 3600)]
    public function find(int $id): ?Category
    {
        return $this->categoryService->findById($id);
    }

    public function options(int $excludeId = 0): array
    {
        return $this->categoryService->getOptions($excludeId);
    }

    public function statistics(): array
    {
        return [
            'total' => Category::count(),
            'active' => Category::where('status', CategoryStatus::ACTIVE->value)->count(),
            'inactive' => Category::where('status', CategoryStatus::INACTIVE->value)->count(),
            'root' => Category::where('parent_id', 0)->count(),
            'by_level' => $this->getCountsByLevel(),
        ];
    }

    public function breadcrumb(int $categoryId): array
    {
        $category = $this->categoryService->findById($categoryId);
        if (! $category) {
            return [];
        }

        $breadcrumb = [];
        while ($category) {
            array_unshift($breadcrumb, Arr::only($category->toArray(), ['id', 'name', 'parent_id']));
            if ($category->parent_id <= 0) {
                break;
            }
            $category = $this->categoryService->findById((int) $category->parent_id);
        }

        return $breadcrumb;
    }

    private function getCountsByLevel(): array
    {
        $counts = [];
        for ($level = 1; $level <= Category::MAX_LEVEL; ++$level) {
            $counts["level{$level}"] = Category::where('level', $level)->count();
        }
        return $counts;
    }

    private function convertTreeToArray(iterable $categories): array
    {
        $result = [];
        foreach ($categories as $category) {
            $item = Arr::only($category->toArray(), [
                'id',
                'parent_id',
                'name',
                'icon',
                'thumbnail',
                'description',
                'sort',
                'level',
                'status',
                'created_at',
                'updated_at',
            ]);
            $item['children'] = ($category->relationLoaded('allChildren') && $category->allChildren->isNotEmpty())
                ? $this->convertTreeToArray($category->allChildren)
                : [];
            $result[] = $item;
        }
        return $result;
    }
}
