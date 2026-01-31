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

namespace App\Domain\Product\Trait;

use App\Domain\Product\Entity\ProductAttributeEntity;
use App\Domain\Product\Entity\ProductEntity;
use App\Domain\Product\Entity\ProductSkuEntity;
use App\Infrastructure\Model\Product\Product;
use App\Infrastructure\Model\Product\ProductAttribute;
use App\Infrastructure\Model\Product\ProductSku;
use Hyperf\Collection\Collection;

trait ProductMapperTrait
{
    /**
     * 映射.
     */
    public static function mapper(Product $model): ProductEntity
    {
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
        $entity->setAttributesJson($model->attributes);
        $entity->setMinPrice((float) $model->min_price);
        $entity->setMaxPrice((float) $model->max_price);
        $entity->setVirtualSales($model->virtual_sales);
        $entity->setRealSales($model->real_sales);
        $entity->setIsRecommend($model->is_recommend);
        $entity->setIsHot($model->is_hot);
        $entity->setIsNew($model->is_new);
        $entity->setShippingTemplateId($model->shipping_template_id);
        $entity->setSort($model->sort);
        $entity->setStatus($model->status);

        /** @var Collection<int, ProductSku> $skuModels */
        $skuModels = $model->skus()->get();
        // sku 映射
        $entity->setSkus($skuModels->map(static function (ProductSku $sku) {
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
            $skuEntity->setWeight($sku->weight);
            $skuEntity->setWarningStock($sku->warning_stock);
            return $skuEntity;
        })->all());

        /** @var Collection<int, ProductAttribute> $attributeModels */
        $attributeModels = $model->attributes()->get();
        // 属性映射
        $entity->setAttributes($attributeModels->map(static function (ProductAttribute $attribute) {
            $attributeEntity = new ProductAttributeEntity();
            $attributeEntity->setId((int) $attribute->id);
            $attributeEntity->setAttributeName($attribute->attribute_name);
            $attributeEntity->setValue($attribute->value);
            return $attributeEntity;
        })->all());

        return $entity;
    }
}
