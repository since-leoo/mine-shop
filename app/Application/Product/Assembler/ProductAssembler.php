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

namespace App\Application\Product\Assembler;

use App\Domain\Product\Entity\ProductAttributeEntity;
use App\Domain\Product\Entity\ProductEntity;
use App\Domain\Product\Entity\ProductSkuEntity;
use App\Domain\Product\Enum\ProductStatus;

/**
 * 商品组装器：负责将请求数据转换为领域实体.
 */
final class ProductAssembler
{
    private const DEFAULT_PRICE = 0.0;

    /**
     * @param array<string, mixed> $payload
     */
    public static function toCreateEntity(array $payload): ProductEntity
    {
        $entity = new ProductEntity();
        self::fillBaseFields($entity, $payload, ProductStatus::DRAFT->value);

        $entity->setSkus(self::mapSkus($payload['skus'] ?? []));
        $entity->setAttributes(self::mapAttributes($payload['attributes'] ?? []));
        $entity->setGallery($payload['gallery'] ?? []);

        return $entity;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function toUpdateEntity(int $id, array $payload): ProductEntity
    {
        $entity = new ProductEntity();
        $entity->setId($id);
        self::fillBaseFields($entity, $payload, null, false);
        $entity->setSkus(self::mapSkus($payload['skus'] ?? []));
        $entity->setAttributes(self::mapAttributes($payload['product_attributes'] ?? []));
        $entity->setGallery($payload['gallery'] ?? []);

        return $entity;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function fillBaseFields(ProductEntity $entity, array $payload, ?string $defaultStatus = null, bool $forceBrandUpdate = true): void
    {
        $entity->setProductCode($payload['product_code'] ?? null);
        $entity->setCategoryId(isset($payload['category_id']) ? (int) $payload['category_id'] : null);
        $entity->setBrandId(isset($payload['brand_id']) ? (int) $payload['brand_id'] : null);
        $entity->setName($payload['name'] ?? null);
        $entity->setSubTitle($payload['sub_title'] ?? null);
        $entity->setMainImage($payload['main_image'] ?? null);
        $entity->setGalleryImages(\is_array($payload['gallery_images'] ?? null) ? $payload['gallery_images'] : null);
        $entity->setDescription($payload['description'] ?? null);
        $entity->setDetailContent($payload['detail_content'] ?? null);
        $entity->setAttributesJson($payload['attributes'] ?? null);
        $entity->setMinPrice(isset($payload['min_price']) ? (float) $payload['min_price'] : ($forceBrandUpdate ? self::DEFAULT_PRICE : null));
        $entity->setMaxPrice(isset($payload['max_price']) ? (float) $payload['max_price'] : ($forceBrandUpdate ? self::DEFAULT_PRICE : null));
        $entity->setVirtualSales(isset($payload['virtual_sales']) ? (int) $payload['virtual_sales'] : null);
        $entity->setRealSales(isset($payload['real_sales']) ? (int) $payload['real_sales'] : null);
        $entity->setIsRecommend(isset($payload['is_recommend']) ? (bool) $payload['is_recommend'] : null);
        $entity->setIsHot(isset($payload['is_hot']) ? (bool) $payload['is_hot'] : null);
        $entity->setIsNew(isset($payload['is_new']) ? (bool) $payload['is_new'] : null);
        $entity->setShippingTemplateId(isset($payload['shipping_template_id']) ? (int) $payload['shipping_template_id'] : null);
        $entity->setSort(isset($payload['sort']) ? (int) $payload['sort'] : null);

        $status = $payload['status'] ?? null;
        if ($status === null && $defaultStatus !== null) {
            $status = $defaultStatus;
        }
        $entity->changeStatus($status);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return null|ProductSkuEntity[]
     */
    private static function mapSkus(array $items): ?array
    {
        if ($items === []) {
            return null;
        }

        return array_map(static function (array $item): ProductSkuEntity {
            $sku = new ProductSkuEntity();
            $sku->setId(isset($item['id']) ? (int) $item['id'] : null);
            $sku->setSkuCode($item['sku_code'] ?? null);
            $sku->setSkuName((string) ($item['sku_name'] ?? ''));
            $sku->setSpecValues($item['spec_values'] ?? null);
            $sku->setImage($item['image'] ?? null);
            $sku->setCostPrice(isset($item['cost_price']) ? (float) $item['cost_price'] : 0.0);
            $sku->setMarketPrice(isset($item['market_price']) ? (float) $item['market_price'] : 0.0);
            $sku->setSalePrice(isset($item['sale_price']) ? (float) $item['sale_price'] : 0.0);
            $sku->setStock(isset($item['stock']) ? (int) $item['stock'] : 0);
            $sku->setWarningStock(isset($item['warning_stock']) ? (int) $item['warning_stock'] : 0);
            $sku->setWeight(isset($item['weight']) ? (float) $item['weight'] : 0.0);
            $sku->setStatus($item['status'] ?? null);

            return $sku;
        }, $items);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return null|ProductAttributeEntity[]
     */
    private static function mapAttributes(array $items): ?array
    {
        if ($items === []) {
            return null;
        }

        return array_map(static function (array $item): ProductAttributeEntity {
            $attribute = new ProductAttributeEntity();
            $attribute->setId(isset($item['id']) ? (int) $item['id'] : null);
            $attribute->setAttributeName((string) ($item['attribute_name'] ?? ''));
            $attribute->setValue((string) ($item['value'] ?? ''));

            return $attribute;
        }, $items);
    }
}
