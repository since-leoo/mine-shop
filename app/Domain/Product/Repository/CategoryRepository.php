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
 * 分类数据仓库类.
 *
 * @extends IRepository<Category>
 */
final class CategoryRepository extends IRepository
{
    public function __construct(protected readonly Category $model) {}

    /**
     * 获取分页数据.
     *
     * @param array $params 查询参数
     * @param null|int $page 页码
     * @param null|int $pageSize 每页大小
     * @return array 包含列表和总数的数组
     */
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

    /**
     * 创建分类.
     *
     * @param CategoryEntity $entity 分类实体对象
     * @return Category 创建后的分类模型实例
     */
    public function store(CategoryEntity $entity): Category
    {
        $category = Category::create($entity->toArray());
        $entity->setId($category->id);
        return $category;
    }

    /**
     * 更新分类信息.
     *
     * @param CategoryEntity $entity 分类实体对象
     * @return bool 更新是否成功
     */
    public function update(CategoryEntity $entity): bool
    {
        $category = Category::find($entity->getId());
        return $category && $category->update($entity->toArray());
    }

    /**
     * 获取分类选项数据.
     *
     * @param int $excludeId 需要排除的分类ID
     * @return array 分类选项数组
     */
    public function getOptions(int $excludeId = 0): array
    {
        return Category::getOptions($excludeId);
    }

    /**
     * 批量更新分类排序.
     *
     * @param array $sortData 排序数据数组
     * @return bool 更新是否成功
     */
    public function updateSort(array $sortData): bool
    {
        // 使用事务确保批量更新操作的一致性
        return (bool) Db::transaction(static function () use ($sortData) {
            foreach ($sortData as $item) {
                Category::where('id', $item['id'])->update(['sort' => $item['sort']]);
            }
            return true;
        });
    }

    /**
     * 获取分类树形结构.
     *
     * @param int $parentId 父级分类ID
     * @return Collection 分类树形集合
     */
    public function getTree(int $parentId = 0): Collection
    {
        return $this->model::where('parent_id', $parentId)
            ->active()
            ->ordered()
            ->with(['allChildren' => static function ($query) {
                $query->active()->ordered();
            }])
            ->get();
    }

    /**
     * 处理搜索条件.
     *
     * @param Builder $query 查询构建器
     * @param array $params 搜索参数
     * @return Builder 处理后的查询构建器
     */
    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(! empty($params['name']), static fn (Builder $q) => $q->where('name', 'like', '%' . $params['name'] . '%'))
            ->when(! empty($params['keyword']), static fn (Builder $q) => $q->where('name', 'like', '%' . $params['keyword'] . '%'))
            ->when(! empty($params['status']), static fn (Builder $q) => $q->where('status', $params['status']))
            ->when(! empty($params['parent_id']), static fn (Builder $q) => $q->where('parent_id', $params['parent_id']))
            ->when(! empty($params['level']), static fn (Builder $q) => $q->where('level', $params['level']));
    }
}
