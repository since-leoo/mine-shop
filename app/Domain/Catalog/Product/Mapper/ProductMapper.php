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

namespace App\Domain\Catalog\Product\Mapper;

use App\Domain\Catalog\Product\Entity\ProductAttributeEntity;
use App\Domain\Catalog\Product\Entity\ProductEntity;
use App\Domain\Catalog\Product\Entity\ProductSkuEntity;
use App\Infrastructure\Model\Product\Product;
use App\Infrastructure\Model\Product\ProductAttribute;
use App\Infrastructure\Model\Product\ProductSku;
use Hyperf\Collection\Collection;

final class ProductMapper
{
    /**
     * 获取新实体.
     */
    public static function getNewEntity(): ProductEntity
    {
        return new ProductEntity();
    }

    /**
     * 从模型还原商品实体.
     */
    public static function fromModel(Product $model): ProductEntity
    {
        // 先保存 attributes JSON 字段的值
        $attributesJson = $model->attributes;

        // 加载关联关系（这会覆盖 attributes 字段）
        $model->loadMissing(['skus', 'attributes', 'gallery']);

        $entity = new ProductEntity();
        $entity->setId((int) $model->id);
        $entity->setProductCode($model->product_code);
        $entity->setCategoryId($model->category_id);
        $entity->setBrandId($model->brand_id);
        $entity->setName($model->name);
        $entity->setSubTitle($model->sub_title);
        $entity->setMainImage($model->main_image);
        $entity->setGalleryImages($model->gallery_images);
        $entity->setDescription($model->description);
        $entity->setDetailContent($model->detail_content);

        // 使用之前保存的 attributes JSON 字段值
        $entity->setAttributesJson(\is_array($attributesJson) ? $attributesJson : null);

        $entity->setMinPrice($model->min_price);
        $entity->setMaxPrice($model->max_price);
        $entity->setVirtualSales($model->virtual_sales);
        $entity->setRealSales($model->real_sales);
        $entity->setIsRecommend($model->is_recommend);
        $entity->setIsHot($model->is_hot);
        $entity->setIsNew($model->is_new);
        $entity->setShippingTemplateId($model->shipping_template_id);
        $entity->setFreightType($model->freight_type);
        $entity->setFlatFreightAmount($model->flat_freight_amount !== null ? (int) $model->flat_freight_amount : null);
        $entity->setSort($model->sort);
        $entity->setStatus($model->status);

        // 处理 SKU 关联关系
        $skuModels = $model->skus;
        $entity->setSkus($skuModels->map(static fn (ProductSku $sku) => self::mapSkuModel($sku))->all());

        // 处理属性关联关系（product_attributes 表）
        $attributeModels = $model->getRelation('attributes');
        if ($attributeModels instanceof Collection) {
            $entity->setAttributes($attributeModels->map(static fn (ProductAttribute $attribute) => self::mapAttributeModel($attribute))->all());
        } elseif (\is_array($attributeModels)) {
            // 如果是数组，直接使用
            $entity->setAttributes(array_map(static fn ($attribute) => self::mapAttributeModel($attribute), $attributeModels));
        } else {
            $entity->setAttributes([]);
        }

        // 处理图片集关联关系
        $galleryModels = $model->gallery;
        $entity->setGallery($galleryModels->map(static fn ($gallery) => $gallery->toArray())->all());

        return $entity;
    }

    /**
     * 将实体转换为持久化数组.
     *
     * @return array<string, mixed>
     */
    public static function toArray(ProductEntity $entity, ?Product $model = null): array
    {
        return $entity->toArray($model);
    }

    private static function mapSkuModel(ProductSku $sku): ProductSkuEntity
    {
        $skuEntity = new ProductSkuEntity();
        $skuEntity->setId((int) $sku->id);
        $skuEntity->setSkuCode($sku->sku_code);
        $skuEntity->setSkuName($sku->sku_name);
        $skuEntity->setStock((int) $sku->stock);
        $skuEntity->setCostPrice((int) $sku->cost_price);
        $skuEntity->setImage($sku->image);
        $skuEntity->setMarketPrice((int) $sku->market_price);
        $skuEntity->setSalePrice((int) $sku->sale_price);
        $skuEntity->setStatus($sku->status);
        $skuEntity->setSpecValues($sku->spec_values);
        $skuEntity->setWeight((float) $sku->weight);
        $skuEntity->setWarningStock((int) $sku->warning_stock);
        return $skuEntity;
    }

    private static function mapAttributeModel(ProductAttribute $attribute): ProductAttributeEntity
    {
        $attributeEntity = new ProductAttributeEntity();
        $attributeEntity->setId((int) $attribute->id);
        $attributeEntity->setAttributeName($attribute->attribute_name);
        $attributeEntity->setValue($attribute->value);
        return $attributeEntity;
    }
}
