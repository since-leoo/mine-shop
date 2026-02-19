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

namespace App\Infrastructure\Abstract;

use App\Infrastructure\Traits\BootTrait;
use App\Infrastructure\Traits\RepositoryOrderByTrait;
use Hyperf\Collection\Collection;
use Hyperf\Contract\LengthAwarePaginatorInterface;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Model;
use Hyperf\DbConnection\Traits\HasContainer;
use Hyperf\Paginator\AbstractPaginator;

/**
 * 仓储抽象基类.
 *
 * 提供通用的 CRUD 操作、分页查询、搜索过滤等功能。
 * 子类需要注入具体的 Model 实例，并可重写 handleSearch 方法自定义搜索逻辑。
 *
 * @template T of Model
 * @property Model $model
 */
abstract class IRepository
{
    use BootTrait;
    use HasContainer;
    use RepositoryOrderByTrait;

    /**
     * 分页参数名.
     */
    public const PER_PAGE_PARAM_NAME = 'per_page';

    /**
     * 处理搜索条件.
     *
     * 子类重写此方法以实现自定义的搜索逻辑。
     *
     * @param Builder $query 查询构建器
     * @param array $params 搜索参数
     * @return Builder 处理后的查询构建器
     */
    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query;
    }

    /**
     * 处理列表项.
     *
     * 子类重写此方法以加载关联数据或转换数据格式。
     *
     * @param Collection $items 数据集合
     * @return Collection 处理后的数据集合
     */
    public function handleItems(Collection $items): Collection
    {
        return $items;
    }

    /**
     * 处理分页结果.
     *
     * @param LengthAwarePaginatorInterface $paginator 分页器
     * @return array 包含 list 和 total 的数组
     */
    public function handlePage(LengthAwarePaginatorInterface $paginator): array
    {
        if ($paginator instanceof AbstractPaginator) {
            $items = $paginator->getCollection();
        } else {
            $items = Collection::make($paginator->items());
        }
        $items = $this->handleItems($items);
        return [
            'list' => $items->toArray(),
            'total' => $paginator->total(),
        ];
    }

    /**
     * 获取列表数据.
     *
     * @param array $params 筛选参数
     * @return Collection 数据集合
     */
    public function list(array $params = []): Collection
    {
        return $this->handleItems($this->perQuery($this->getQuery(), $params)->get());
    }

    /**
     * 统计数量.
     *
     * @param array $params 筛选参数
     * @return int 记录数量
     */
    public function count(array $params = []): int
    {
        return $this->perQuery($this->getQuery(), $params)->count();
    }

    /**
     * 分页查询.
     *
     * @param array $params 筛选参数
     * @param int|null $page 页码
     * @param int|null $pageSize 每页数量
     * @return array 包含 list 和 total 的数组
     */
    public function page(array $params = [], ?int $page = null, ?int $pageSize = null): array
    {
        $result = $this->perQuery($this->getQuery(), $params)->paginate(
            perPage: $pageSize,
            pageName: static::PER_PAGE_PARAM_NAME,
            page: $page,
        );
        return $this->handlePage($result);
    }

    /**
     * 根据 ID 查找记录.
     *
     * @param int $id 主键 ID
     * @return object|null 模型实例或 null
     */
    public function findById(int $id): ?object
    {
        return $this->getQuery()->whereKey($id)->first();
    }

    /**
     * 根据 ID 查找记录并加锁.
     *
     * 使用 FOR UPDATE 行锁，用于并发更新场景。
     *
     * @param mixed $id 主键 ID
     * @return Model|null 模型实例或 null
     */
    public function findByIdForLock(mixed $id)
    {
        return $this->getQuery()->whereKey($id)->lockForUpdate()->first();
    }

    /**
     * 创建记录.
     *
     * @param array $payload 数据数组
     * @return Model 创建的模型实例
     */
    public function create(array $payload): mixed
    {
        return $this->model->newQuery()->create($payload);
    }

    /**
     * 根据 ID 更新记录.
     *
     * @param int $id 主键 ID
     * @param array $data 更新数据
     * @return bool 是否更新成功
     */
    public function updateById(int $id, array $data): bool
    {
        return (bool) $this->getQuery()->whereKey($id)->first()?->update($data);
    }

    /**
     * 根据 ID 保存记录.
     *
     * 与 updateById 不同，此方法返回更新后的模型实例。
     *
     * @param mixed $id 主键 ID
     * @param array $data 更新数据
     * @return Model|null 更新后的模型实例或 null
     */
    public function saveById(mixed $id, array $data): mixed
    {
        $model = $this->getQuery()->whereKey($id)->first();
        if ($model) {
            $model->fill($data)->save();
            return $model;
        }
        return null;
    }

    /**
     * 根据 ID 删除记录.
     *
     * @param mixed $id 主键 ID
     * @return int 删除的记录数
     */
    public function deleteById(mixed $id): int
    {
        // @phpstan-ignore-next-line
        return $this->model::destroy($id);
    }

    /**
     * 批量删除记录.
     *
     * @param array $ids 主键 ID 数组
     * @return int 删除的记录数
     */
    public function deleteByIds(array $ids): int
    {
        array_map(function ($id) {
            $info = $this->findById($id);
            $info && $info->delete();
        }, $ids);

        return \count($ids);
    }

    /**
     * 强制删除记录（包括软删除的记录）.
     *
     * @param mixed $id 主键 ID
     * @return bool 是否删除成功
     */
    public function forceDeleteById(mixed $id): bool
    {
        return (bool) $this->getQuery()->whereKey($id)->forceDelete();
    }

    /**
     * 获取指定记录的某个字段值.
     *
     * @param mixed $id 主键 ID
     * @param string $field 字段名
     * @return mixed 字段值
     */
    public function findByField(mixed $id, string $field): mixed
    {
        return $this->getQuery()->whereKey($id)->value($field);
    }

    /**
     * 根据筛选条件查找单条记录.
     *
     * @param array $params 筛选参数
     * @return Model|null 模型实例或 null
     */
    public function findByFilter(array $params): mixed
    {
        return $this->perQuery($this->getQuery(), $params)->first();
    }

    /**
     * 预处理查询.
     *
     * 执行启动钩子并应用搜索条件。
     *
     * @param Builder $query 查询构建器
     * @param array $params 参数
     * @return Builder 处理后的查询构建器
     */
    public function perQuery(Builder $query, array $params): Builder
    {
        $this->startBoot($query, $params);
        return $this->handleSearch($query, $params);
    }

    /**
     * 获取新的查询构建器.
     *
     * @return Builder 查询构建器
     */
    public function getQuery(): Builder
    {
        return $this->model->newQuery();
    }

    /**
     * 检查记录是否存在.
     *
     * @param mixed $id 主键 ID
     * @return bool 是否存在
     */
    public function existsById(mixed $id): bool
    {
        return (bool) $this->getQuery()->whereKey($id)->exists();
    }

    /**
     * 获取模型实例.
     *
     * @return Model 模型实例
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * 导出数据提供者.
     *
     * 使用游标查询避免内存溢出，适合大数据量导出。
     * 子类可重写此方法以自定义导出逻辑（如展开关联数据）。
     *
     * @param array $params 筛选参数
     * @return iterable 数据生成器
     */
    public function getExportData(array $params): iterable
    {
        $query = $this->perQuery($this->getQuery(), $params);

        foreach ($query->cursor() as $row) {
            yield $row;
        }
    }
}
