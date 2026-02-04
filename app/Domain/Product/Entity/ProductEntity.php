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

use App\Domain\Product\Enum\ProductStatus;
use App\Domain\Product\Trait\ProductEntityTrait;
use App\Domain\Product\Trait\ProductSettingsTrait;
use App\Infrastructure\Model\Product\Product;

/**
 * 商品聚合根.
 */
final class ProductEntity
{
    use ProductEntityTrait;
    use ProductSettingsTrait;

    private const STATUS_TRANSITIONS = [
        ProductStatus::DRAFT->value => [
            ProductStatus::ACTIVE->value,
            ProductStatus::INACTIVE->value,
        ],
        ProductStatus::ACTIVE->value => [
            ProductStatus::INACTIVE->value,
            ProductStatus::SOLD_OUT->value,
        ],
        ProductStatus::INACTIVE->value => [
            ProductStatus::ACTIVE->value,
            ProductStatus::SOLD_OUT->value,
        ],
        ProductStatus::SOLD_OUT->value => [
            ProductStatus::INACTIVE->value,
        ],
    ];

    private int $id = 0;

    private ?string $productCode = null;

    private ?int $categoryId = null;

    private ?int $brandId = null;

    private ?string $name = null;

    private ?string $subTitle = null;

    private ?string $mainImage = null;

    /** @var null|string[] */
    private ?array $galleryImages = null;

    private ?string $description = null;

    private ?string $detailContent = null;

    /** @var null|array<string, mixed> */
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

    /** @var null|ProductSkuEntity[] */
    private ?array $skus = null;

    /** @var null|ProductAttributeEntity[] */
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
     * @return null|array<string>
     */
    public function getGalleryImages(): ?array
    {
        return $this->galleryImages;
    }

    /**
     * @param null|array<string> $galleryImages
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
     * @return null|array<string, mixed>
     */
    public function getAttributesJson(): ?array
    {
        return $this->attributesJson;
    }

    /**
     * @param null|array<string, mixed> $attributes
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
        if ($sort === null) {
            $this->sort = null;
            return;
        }
        $this->applySort($sort);
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        if ($status === null) {
            $this->status = null;
            return;
        }
        $this->changeStatus($status);
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
     * @return null|ProductSkuEntity[]
     */
    public function getSkus(): ?array
    {
        return $this->skus;
    }

    /**
     * @param null|ProductSkuEntity[] $skus
     */
    public function setSkus(?array $skus): void
    {
        if ($skus === null) {
            $this->skus = null;
            return;
        }

        foreach ($skus as $index => $sku) {
            if (! $sku instanceof ProductSkuEntity) {
                throw new \DomainException('SKU 数据必须通过实体传递');
            }
            $skus[$index] = $sku;
        }

        $this->skus = array_values($skus);
    }

    /**
     * @return null|ProductAttributeEntity[]
     */
    public function getAttributes(): ?array
    {
        return $this->attributes;
    }

    /**
     * @param null|ProductAttributeEntity[] $attributes
     */
    public function setAttributes(?array $attributes): void
    {
        if ($attributes === null) {
            $this->attributes = null;
            return;
        }

        foreach ($attributes as $index => $attribute) {
            if (! $attribute instanceof ProductAttributeEntity) {
                throw new \DomainException('商品属性必须通过实体传递');
            }
            $attributes[$index] = $attribute;
        }

        $this->attributes = array_values($attributes);
    }

    public function getGallery(): array
    {
        return $this->gallery;
    }

    public function setGallery(array $gallery): void
    {
        $this->gallery = $gallery;
    }

    public function applySort(int $sort): self
    {
        $this->sort = max(0, $sort);
        return $this;
    }

    public function addSku(ProductSkuEntity $sku): self
    {
        $this->skus ??= [];
        $this->skus[] = $sku;
        return $this;
    }

    public function removeSkuById(int $skuId): self
    {
        if ($this->skus === null) {
            return $this;
        }

        $this->skus = array_values(array_filter(
            $this->skus,
            static fn (ProductSkuEntity $item) => $item->getId() !== $skuId
        ));

        return $this;
    }

    public function changeStatus(?string $targetStatus): self
    {
        if ($targetStatus === null) {
            return $this;
        }

        if (! \in_array($targetStatus, ProductStatus::values(), true)) {
            throw new \DomainException('无效的商品状态');
        }

        $current = $this->status ?? ProductStatus::DRAFT->value;
        if ($current === $targetStatus) {
            $this->status = $targetStatus;
            return $this;
        }

        $allowed = self::STATUS_TRANSITIONS[$current] ?? [];
        if (! \in_array($targetStatus, $allowed, true)) {
            throw new \DomainException(\sprintf('商品状态不允许从 %s 变更为 %s', $current, $targetStatus));
        }

        $this->status = $targetStatus;
        return $this;
    }

    public function activate(): self
    {
        return $this->changeStatus(ProductStatus::ACTIVE->value);
    }

    public function deactivate(): self
    {
        return $this->changeStatus(ProductStatus::INACTIVE->value);
    }

    public function markSoldOut(): self
    {
        return $this->changeStatus(ProductStatus::SOLD_OUT->value);
    }

    public function ensureCanPersist(bool $isCreate = false): void
    {
        if ($isCreate) {
            $this->assertCreateRequirements();
        } else {
            $this->assertUpdateRequirements();
        }

        $this->ensurePriceRangeIntegrity();
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

    private function assertCreateRequirements(): void
    {
        $this->assertRequiredString($this->name, '商品名称不能为空');
        $this->assertPositiveInt($this->categoryId, '请选择商品分类');

        $skus = $this->getSkus();
        if ($skus === null || $skus === []) {
            throw new \DomainException('请至少添加一个SKU');
        }

        $this->assertSkuIntegrity($skus);
    }

    private function assertUpdateRequirements(): void
    {
        if ($this->name !== null) {
            $this->assertRequiredString($this->name, '商品名称不能为空');
        }

        if ($this->categoryId !== null) {
            $this->assertPositiveInt($this->categoryId, '商品分类无效');
        }

        $skus = $this->getSkus();
        if ($skus === null) {
            return;
        }

        if ($skus === []) {
            throw new \DomainException('SKU 列表不能为空');
        }

        $this->assertSkuIntegrity($skus);
    }

    private function assertRequiredString(?string $value, string $message): void
    {
        if ($value === null || trim($value) === '') {
            throw new \DomainException($message);
        }
    }

    private function assertPositiveInt(?int $value, string $message): void
    {
        if ($value === null || $value <= 0) {
            throw new \DomainException($message);
        }
    }

    /**
     * @param ProductSkuEntity[] $skus
     */
    private function assertSkuIntegrity(array $skus): void
    {
        foreach ($skus as $sku) {
            if (! $sku instanceof ProductSkuEntity) {
                throw new \DomainException('SKU 数据必须通过实体传递');
            }
            $sku->assertIntegrity();
        }
    }

    private function ensurePriceRangeIntegrity(): void
    {
        if ($this->minPrice === null || $this->maxPrice === null) {
            return;
        }

        if ($this->minPrice > $this->maxPrice) {
            throw new \DomainException('最低价不能高于最高价');
        }
    }
}
