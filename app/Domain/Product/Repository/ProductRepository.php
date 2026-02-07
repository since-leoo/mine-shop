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

use App\Domain\Product\Entity\ProductEntity;
use App\Domain\Product\Mapper\ProductMapper;
use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Product\Product;
use Hyperf\Database\Model\Builder;

/**
 * @extends IRepository<Product>
 */
final class ProductRepository extends IRepository
{
    public function __construct(protected readonly Product $model) {}

    public function findById(int $id): ?object
    {
        return $this->model::with(['skus', 'attributes', 'gallery'])->whereKey($id)->first();
    }

    /**
     * 保存商品
     */
    public function save(ProductEntity $entity): ProductEntity
    {
        /** @var Product $model */
        $model = $this->create(ProductMapper::toArray($entity));
        $model->skus()->createMany(array_map(static function ($sku) {return $sku->toArray(); }, $entity->getSkus()));
        $model->attributes()->createMany(array_map(static function ($attr) {return $attr->toArray(); }, $entity->getAttributes()));

        $entity->setId($model->id);
        return $entity;
    }

    /**
     * 更新商品
     *
     * return void
     */
    public function update(ProductEntity $entity): void
    {
        /** @var Product $model */
        $model = $this->findById($entity->getId());
        $data = ProductMapper::toArray($entity, $model);
        $model->update($data);

        // 处理 SKU 的增删改
        if ($entity->getSkus()) {
            foreach ($entity->getSkus() as $sku) {
                $skuData = $sku->toArray();

                if ($sku->getId()) {
                    // 使用模型实例更新，确保 $casts 生效（spec_values 等 JSON 字段）
                    $skuModel = $model->skus()->where('id', $sku->getId())->first();
                    if ($skuModel) {
                        $skuModel->fill($skuData);
                        $skuModel->save();
                    }
                } else {
                    // 创建新的 SKU（移除 id 字段）
                    unset($skuData['id']);
                    $model->skus()->create($skuData);
                }
            }
        }

        // 删除不在新列表中的 SKU
        if (! empty($data['delete_sku_ids'])) {
            $model->skus()->whereIn('id', $data['delete_sku_ids'])->delete();
        }

        // 处理 attributes 的增删改
        if ($entity->getAttributes()) {
            foreach ($entity->getAttributes() as $attr) {
                $attrData = $attr->toArray();

                if ($attr->getId()) {
                    // 更新已存在的属性
                    $model->attributes()->where('id', $attr->getId())->update($attrData);
                } else {
                    // 创建新的属性（移除 id 字段）
                    unset($attrData['id']);
                    $model->attributes()->create($attrData);
                }
            }
        }

        // 删除不在新列表中的属性
        if (! empty($data['delete_attr_ids'])) {
            $model->attributes()->whereIn('id', $data['delete_attr_ids'])->delete();
        }
    }

    public function remove(ProductEntity $entity): void
    {
        $model = $this->findById($entity->getId());
        if (! $model) {
            return;
        }

        $model->skus()->delete();
        $model->attributes()->delete();
        $model->gallery()->delete();
        $this->deleteById($entity->getId());
    }

    public function handleSearch(Builder $query, array $params): Builder
    {
        // 标准化布尔值参数（处理字符串 "true"/"false" 和数字 1/0）
        foreach (['is_recommend', 'is_hot', 'is_new'] as $field) {
            if (isset($params[$field])) {
                if ($params[$field] === 'true' || $params[$field] === '1' || $params[$field] === 1) {
                    $params[$field] = true;
                } elseif ($params[$field] === 'false' || $params[$field] === '0' || $params[$field] === 0) {
                    $params[$field] = false;
                }
            }
        }

        return $query
            ->when(! empty($params['name']), static fn (Builder $q) => $q->where('name', 'like', '%' . $params['name'] . '%'))
            ->when(! empty($params['keyword']), static fn (Builder $q) => $q->where(static fn (Builder $q) => $q->where('name', 'like', '%' . $params['keyword'] . '%')->orWhere('product_code', 'like', '%' . $params['keyword'] . '%')))
            ->when(! empty($params['product_code']), static fn (Builder $q) => $q->where('product_code', 'like', '%' . $params['product_code'] . '%'))
            ->when(! empty($params['category_id']), static fn (Builder $q) => \is_array($params['category_id']) ? $q->whereIn('category_id', $params['category_id']) : $q->where('category_id', $params['category_id']))
            ->when(! empty($params['brand_id']), static fn (Builder $q) => \is_array($params['brand_id']) ? $q->whereIn('brand_id', $params['brand_id']) : $q->where('brand_id', $params['brand_id']))
            ->when(! empty($params['status']), static fn (Builder $q) => $q->where('status', $params['status']))
            ->when(isset($params['is_recommend']), static fn (Builder $q) => $q->where('is_recommend', (bool) $params['is_recommend']))
            ->when(isset($params['is_hot']), static fn (Builder $q) => $q->where('is_hot', (bool) $params['is_hot']))
            ->when(isset($params['is_new']), static fn (Builder $q) => $q->where('is_new', (bool) $params['is_new']))
            ->when(isset($params['min_price']) && $params['min_price'] !== '', static fn (Builder $q) => $q->where('min_price', '>=', (int) $params['min_price']))
            ->when(isset($params['max_price']) && $params['max_price'] !== '', static fn (Builder $q) => $q->where('max_price', '<=', (int) $params['max_price']))
            ->when(isset($params['sales_min']) && $params['sales_min'] !== '', static fn (Builder $q) => $q->where('real_sales', '>=', (int) $params['sales_min']))
            ->when(isset($params['sales_max']) && $params['sales_max'] !== '', static fn (Builder $q) => $q->where('real_sales', '<=', (int) $params['sales_max']))
            ->with(['category', 'brand']);
    }
}
