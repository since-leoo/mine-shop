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

namespace App\Domain\Product\Entity;

use App\Domain\Product\Trait\ProductEntityTrait;
use App\Infrastructure\Model\Product\Product;

/**
 * 商品聚合根.
 */
final class ProductEntity
{
    use ProductEntityTrait;

    private int $id = 0;

    private ?string $productCode = null;

    private ?int $categoryId = null;

    private ?int $brandId = null;

    private ?string $name = null;

    private ?string $subTitle = null;

    private ?string $mainImage = null;

    /** @var string[]|null */
    private ?array $galleryImages = null;

    private ?string $description = null;

    private ?string $detailContent = null;

    /** @var array<string, mixed>|null */
    private ?array $attributesJson = null;

    private ?float $minPrice = null;

    private ?float $maxPrice = null;

    private ?int $virtualSales = null;

    private ?int $realSales = null;

    private ?bool $isRecommend = null;

    private ?bool $isHot = null;

    private ?bool $isNew = null;

    private ?int $shippingTemplateId = null;

    private ?int $sort = null;

    private ?string $status = null;

    /** @var ProductSkuEntity[]|null */
    private ?array $skus = null;

    /** @var ProductAttributeEntity[]|null */
    private ?array $attributes = null;

    /** @var array<int, mixed> */
    private array $gallery = [];

    public function setProductCode(?string $code): void
    {
        $this->productCode = $code;
    }

    public function getProductCode(): ?string
    {
        return $this->productCode;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function setCategoryId(?int $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    public function getBrandId(): ?int
    {
        return $this->brandId;
    }

    public function setBrandId(?int $brandId): void
    {
        $this->brandId = $brandId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getSubTitle(): ?string
    {
        return $this->subTitle;
    }

    public function setSubTitle(?string $subTitle): void
    {
        $this->subTitle = $subTitle;
    }

    public function getMainImage(): ?string
    {
        return $this->mainImage;
    }

    public function setMainImage(?string $mainImage): void
    {
        $this->mainImage = $mainImage;
    }

    /**
     * @return array<string>|null
     */
    public function getGalleryImages(): ?array
    {
        return $this->galleryImages;
    }

    /**
     * @param array<string>|null $galleryImages
     */
    public function setGalleryImages(?array $galleryImages): void
    {
        $this->galleryImages = $galleryImages;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getDetailContent(): ?string
    {
        return $this->detailContent;
    }

    public function setDetailContent(?string $detailContent): void
    {
        $this->detailContent = $detailContent;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getAttributesJson(): ?array
    {
        return $this->attributesJson;
    }

    /**
     * @param array<string, mixed>|null $attributes
     */
    public function setAttributesJson(?array $attributes): void
    {
        $this->attributesJson = $attributes;
    }

    public function getMinPrice(): ?float
    {
        return $this->minPrice;
    }

    public function setMinPrice(?float $minPrice): void
    {
        $this->minPrice = $minPrice;
    }

    public function getMaxPrice(): ?float
    {
        return $this->maxPrice;
    }

    public function setMaxPrice(?float $maxPrice): void
    {
        $this->maxPrice = $maxPrice;
    }

    public function getVirtualSales(): ?int
    {
        return $this->virtualSales;
    }

    public function setVirtualSales(?int $virtualSales): void
    {
        $this->virtualSales = $virtualSales;
    }

    public function getRealSales(): ?int
    {
        return $this->realSales;
    }

    public function setRealSales(?int $realSales): void
    {
        $this->realSales = $realSales;
    }

    public function getIsRecommend(): ?bool
    {
        return $this->isRecommend;
    }

    public function setIsRecommend(?bool $isRecommend): void
    {
        $this->isRecommend = $isRecommend;
    }

    public function getIsHot(): ?bool
    {
        return $this->isHot;
    }

    public function setIsHot(?bool $isHot): void
    {
        $this->isHot = $isHot;
    }

    public function getIsNew(): ?bool
    {
        return $this->isNew;
    }

    public function setIsNew(?bool $isNew): void
    {
        $this->isNew = $isNew;
    }

    public function getShippingTemplateId(): ?int
    {
        return $this->shippingTemplateId;
    }

    public function setShippingTemplateId(?int $shippingTemplateId): void
    {
        $this->shippingTemplateId = $shippingTemplateId;
    }

    public function getSort(): ?int
    {
        return $this->sort;
    }

    public function setSort(?int $sort): void
    {
        $this->sort = $sort;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return ProductSkuEntity[]|null
     */
    public function getSkus(): ?array
    {
        return $this->skus;
    }

    /**
     * @param ProductSkuEntity[]|null $skus
     */
    public function setSkus(?array $skus): void
    {
        $this->skus = $skus;
    }

    /**
     * @return ProductAttributeEntity[]|null
     */
    public function getAttributes(): ?array
    {
        return $this->attributes;
    }

    /**
     * @param ProductAttributeEntity[]|null $attributes
     */
    public function setAttributes(?array $attributes): void
    {
        $this->attributes = $attributes;
    }

    /**
     * @return array
     */
    public function getGallery(): array
    {
        return $this->gallery;
    }

    /**
     * @param array $gallery
     */
    public function setGallery(array $gallery): void
    {
        $this->gallery = $gallery;
    }

    public function syncPriceRange(): void
    {
        $skus = $this->getSkus();
        if ($skus === null || $skus === []) {
            return;
        }

        $prices = array_filter(
            array_map(static fn (ProductSkuEntity $sku) => $sku->getSalePrice(), $skus),
            static fn (float $price) => $price >= 0.0
        );

        if ($prices === []) {
            $this->setMinPrice(0.0);
            $this->setMaxPrice(0.0);
            return;
        }

        $this->setMinPrice(min($prices));
        $this->setMaxPrice(max($prices));
    }

    /**
     * 转换为数组（用于持久化）。
     *
     * @return array<string, mixed>
     */
    public function toArray(?Product $model = null): array
    {
        return array_filter([
            'product_code' => $this->getProductCode(),
            'category_id' => $this->getCategoryId(),
            'brand_id' => $this->getBrandId(),
            'name' => $this->getName(),
            'sub_title' => $this->getSubTitle(),
            'main_image' => $this->getMainImage(),
            'gallery_images' => $this->getGalleryImages(),
            'description' => $this->getDescription(),
            'detail_content' => $this->getDetailContent(),
            'attributes' => $this->getAttributesJson(),
            'min_price' => $this->getMinPrice(),
            'max_price' => $this->getMaxPrice(),
            'virtual_sales' => $this->getVirtualSales(),
            'real_sales' => $this->getRealSales(),
            'is_recommend' => $this->getIsRecommend(),
            'is_hot' => $this->getIsHot(),
            'is_new' => $this->getIsNew(),
            'shipping_template_id' => $this->getShippingTemplateId(),
            'sort' => $this->getSort(),
            'status' => $this->getStatus(),
            'gallery' => $this->getGallery(),
            'delete_sku_ids' => $this->getDeleteSkuIds($model),
            'delete_attr_ids' => $this->getDeleteAttributeIds($model),
        ], static fn ($v) => $v !== null);
    }
}
