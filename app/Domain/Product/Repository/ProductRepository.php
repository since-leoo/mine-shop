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
use App\Domain\Product\Trait\ProductMapperTrait;
use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Model\Product\Product;
use App\Interface\Common\ResultCode;
use Hyperf\Database\Model\Builder;

/**
 * @extends IRepository<Product>
 */
final class ProductRepository extends IRepository
{
    use ProductMapperTrait;

    public function __construct(protected readonly Product $model) {}

    /**
     * 通过ID获取商品实体.
     */
    public function getEntityById(int $id): ?ProductEntity
    {
        /** @var null|Product $model */
        $model = $this->findById($id);
        return $model ? self::mapper($model) : throw new BusinessException(ResultCode::FORBIDDEN, '商品不存在');
    }

    /**
     * 保存商品
     */
    public function save(ProductEntity $entity): ProductEntity
    {
        /** @var Product $model */
        $model = $this->create($entity->toArray());
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
        $data = $entity->toArray($model);
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
            ->when(isset($params['name']), static fn (Builder $q) => $q->where('name', 'like', '%' . $params['name'] . '%'))
            ->when(isset($params['keyword']), static fn (Builder $q) => $q->where(static fn (Builder $q) => $q->where('name', 'like', '%' . $params['keyword'] . '%')->orWhere('product_code', 'like', '%' . $params['keyword'] . '%')))
            ->when(isset($params['product_code']), static fn (Builder $q) => $q->where('product_code', 'like', '%' . $params['product_code'] . '%'))
            ->when(isset($params['category_id']), static fn (Builder $q) => \is_array($params['category_id']) ? $q->whereIn('category_id', $params['category_id']) : $q->where('category_id', $params['category_id']))
            ->when(isset($params['brand_id']), static fn (Builder $q) => \is_array($params['brand_id']) ? $q->whereIn('brand_id', $params['brand_id']) : $q->where('brand_id', $params['brand_id']))
            ->when(isset($params['status']), static fn (Builder $q) => $q->where('status', $params['status']))
            ->when(isset($params['is_recommend']), static fn (Builder $q) => $q->where('is_recommend', (bool) $params['is_recommend']))
            ->when(isset($params['is_hot']), static fn (Builder $q) => $q->where('is_hot', (bool) $params['is_hot']))
            ->when(isset($params['is_new']), static fn (Builder $q) => $q->where('is_new', (bool) $params['is_new']))
            ->when(isset($params['min_price']), static fn (Builder $q) => $q->where('min_price', '>=', (float) $params['min_price']))
            ->when(isset($params['max_price']), static fn (Builder $q) => $q->where('max_price', '<=', (float) $params['max_price']))
            ->when(isset($params['sales_min']), static fn (Builder $q) => $q->where('real_sales', '>=', (int) $params['sales_min']))
            ->when(isset($params['sales_max']), static fn (Builder $q) => $q->where('real_sales', '<=', (int) $params['sales_max']))
            ->with(['category', 'brand']);
    }
}
