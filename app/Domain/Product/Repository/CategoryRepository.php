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

namespace App\Domain\Product\Repository;

use App\Domain\Product\Entity\CategoryEntity;
use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Product\Category;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Collection;
use Hyperf\DbConnection\Db;

/**
 * @extends IRepository<Category>
 */
final class CategoryRepository extends IRepository
{
    public function __construct(protected readonly Category $model) {}

    public function getTree(int $parentId = 0): Collection
    {
        return Category::getTree($parentId);
    }

    public function page(array $params = [], ?int $page = null, ?int $pageSize = null): array
    {
        $query = $this->handleSearch($this->getQuery(), $params)
            ->orderBy('sort')
            ->orderBy('id');
        $paginator = $query->paginate($pageSize, ['*'], self::PER_PAGE_PARAM_NAME, $page);

        return [
            'list' => $paginator->items(),
            'total' => $paginator->total(),
        ];
    }

    public function store(CategoryEntity $entity): Category
    {
        $category = Category::create($entity->toArray());
        $entity->setId($category->id);
        return $category;
    }

    public function update(CategoryEntity $entity): bool
    {
        $category = Category::find($entity->getId());
        return $category && $category->update($entity->toArray());
    }

    public function getOptions(int $excludeId = 0): array
    {
        return Category::getOptions($excludeId);
    }

    public function updateSort(array $sortData): bool
    {
        return (bool) Db::transaction(static function () use ($sortData) {
            foreach ($sortData as $item) {
                Category::where('id', $item['id'])->update(['sort' => $item['sort']]);
            }
            return true;
        });
    }

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(isset($params['name']), static fn (Builder $q) => $q->where('name', 'like', '%' . $params['name'] . '%'))
            ->when(isset($params['keyword']), static fn (Builder $q) => $q->where('name', 'like', '%' . $params['keyword'] . '%'))
            ->when(isset($params['status']), static fn (Builder $q) => $q->where('status', $params['status']))
            ->when(isset($params['parent_id']), static fn (Builder $q) => $q->where('parent_id', $params['parent_id']))
            ->when(isset($params['level']), static fn (Builder $q) => $q->where('level', $params['level']));
    }
}
