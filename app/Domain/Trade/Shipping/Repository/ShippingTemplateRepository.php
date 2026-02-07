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

namespace App\Domain\Trade\Shipping\Repository;

use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Product\Product;
use App\Infrastructure\Model\Shipping\ShippingTemplate;
use Hyperf\Database\Model\Builder;

/**
 * 运费模板仓储.
 *
 * @extends IRepository<ShippingTemplate>
 */
final class ShippingTemplateRepository extends IRepository
{
    public function __construct(protected readonly ShippingTemplate $model) {}

    /**
     * 创建运费模板.
     *
     * @param array $data 模板数据
     * @return ShippingTemplate 创建后的模板模型实例
     */
    public function store(array $data): ShippingTemplate
    {
        return ShippingTemplate::create($data);
    }

    /**
     * 更新运费模板.
     *
     * @param int $id 模板ID
     * @param array $data 更新数据
     * @return bool 更新是否成功
     */
    public function update(int $id, array $data): bool
    {
        $template = ShippingTemplate::find($id);
        return $template && $template->update($data);
    }

    /**
     * 检查运费模板是否被商品使用.
     *
     * @param int $id 模板ID
     * @return bool 是否被使用
     */
    public function isUsedByProducts(int $id): bool
    {
        return Product::where('shipping_template_id', $id)->exists();
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
            ->when(isset($params['name']), static fn (Builder $q) => $q->where('name', 'like', '%' . $params['name'] . '%'))
            ->when(isset($params['keyword']), static fn (Builder $q) => $q->where('name', 'like', '%' . $params['keyword'] . '%'))
            ->when(isset($params['charge_type']), static fn (Builder $q) => $q->where('charge_type', $params['charge_type']))
            ->when(isset($params['status']), static fn (Builder $q) => $q->where('status', $params['status']))
            ->when(isset($params['is_default']), static fn (Builder $q) => $q->where('is_default', (bool) $params['is_default']))
            ->orderBy('id', 'desc');
    }
}
