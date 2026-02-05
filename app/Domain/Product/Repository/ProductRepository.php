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

        if ($entity->getSkus()) {
            array_map(static function ($sku) use ($model) {
                $model->skus()->updateOrCreate(['id' => $sku->getId()], $sku->toArray());
            }, $entity->getSkus());
        }

        if (! empty($data['delete_sku_ids'])) {
            $model->skus()->whereIn('id', $data['delete_sku_ids'])->delete();
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
        return $query
            ->when(! empty($params['name']), static fn (Builder $q) => $q->where('name', 'like', '%' . $params['name'] . '%'))
            ->when(! empty($params['keyword']), static fn (Builder $q) => $q->where(static fn (Builder $q) => $q->where('name', 'like', '%' . $params['keyword'] . '%')->orWhere('product_code', 'like', '%' . $params['keyword'] . '%')))
            ->when(! empty($params['product_code']), static fn (Builder $q) => $q->where('product_code', 'like', '%' . $params['product_code'] . '%'))
            ->when(! empty($params['category_id']), static fn (Builder $q) => \is_array($params['category_id']) ? $q->whereIn('category_id', $params['category_id']) : $q->where('category_id', $params['category_id']))
            ->when(! empty($params['brand_id']), static fn (Builder $q) => \is_array($params['brand_id']) ? $q->whereIn('brand_id', $params['brand_id']) : $q->where('brand_id', $params['brand_id']))
            ->when(! empty($params['status']), static fn (Builder $q) => $q->where('status', $params['status']))
            ->when(! empty($params['is_recommend']), static fn (Builder $q) => $q->where('is_recommend', (bool) $params['is_recommend']))
            ->when(! empty($params['is_hot']), static fn (Builder $q) => $q->where('is_hot', (bool) $params['is_hot']))
            ->when(! empty($params['is_new']), static fn (Builder $q) => $q->where('is_new', (bool) $params['is_new']))
            ->when(! empty($params['min_price']), static fn (Builder $q) => $q->where('min_price', '>=', (float) $params['min_price']))
            ->when(! empty($params['max_price']), static fn (Builder $q) => $q->where('max_price', '<=', (float) $params['max_price']))
            ->when(! empty($params['sales_min']), static fn (Builder $q) => $q->where('real_sales', '>=', (int) $params['sales_min']))
            ->when(! empty($params['sales_max']), static fn (Builder $q) => $q->where('real_sales', '<=', (int) $params['sales_max']))
            ->with(['category', 'brand']);
    }
}
