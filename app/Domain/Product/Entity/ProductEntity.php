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

use App\Domain\Product\Contract\ProductInput;
use App\Domain\Product\Enum\ProductStatus;
use App\Domain\Product\Trait\ProductEntityTrait;
use App\Domain\Product\Trait\ProductSettingsTrait;
use App\Domain\Product\ValueObject\PriceRangeVo;
use App\Domain\Product\ValueObject\ProductChangeVo;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Model\Product\Product;
use App\Interface\Common\ResultCode;

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

    private ?int $minPrice = null;

    private ?int $maxPrice = null;

    private ?int $virtualSales = null;

    private ?int $realSales = null;

    private ?bool $isRecommend = null;

    private ?bool $isHot = null;

    private ?bool $isNew = null;

    private ?int $shippingTemplateId = null;

    private ?string $freightType = 'default';

    private ?int $flatFreightAmount = 0;

    private ?int $sort = null;

    private ?string $status = null;

    /** @var null|ProductSkuEntity[] */
    private ?array $skus = null;

    /** @var null|ProductAttributeEntity[] */
    private ?array $attributes = null;

    /** @var array<int, mixed> */
    private array $gallery = [];

    /** @var array<string, bool> dirty 追踪机制 */
    private array $dirty = [];

    /**
     * 创建行为方法：接收 DTO，内部组装设置值.
     */
    public function create(ProductInput $input): self
    {
        $this->setProductCode($input->getProductCode());
        $this->setCategoryId($input->getCategoryId());
        $this->setBrandId($input->getBrandId());
        $this->setName($input->getName());
        $this->setSubTitle($input->getSubTitle());
        $this->setMainImage($input->getMainImage());
        $this->setGalleryImages($input->getGalleryImages());
        $this->setDescription($input->getDescription());
        $this->setDetailContent($input->getDetailContent());
        $this->setAttributesJson($input->getAttributes());
        $this->setVirtualSales($input->getVirtualSales() ?? 0);
        $this->setRealSales($input->getRealSales() ?? 0);
        $this->setIsRecommend($input->getIsRecommend() ?? false);
        $this->setIsHot($input->getIsHot() ?? false);
        $this->setIsNew($input->getIsNew() ?? false);
        $this->setShippingTemplateId($input->getShippingTemplateId());
        $this->setFreightType($input->getFreightType() ?? 'default');
        $this->setFlatFreightAmount($input->getFlatFreightAmount() ?? 0);
        $this->setSort($input->getSort() ?? 0);
        $this->setStatus($input->getStatus() ?? ProductStatus::DRAFT->value);
        $this->setGallery($input->getGallery());

        // 处理 SKU
        $skuData = $input->getSkus();
        if ($skuData !== null) {
            $skus = [];
            foreach ($skuData as $item) {
                $sku = new ProductSkuEntity();
                $sku->setSkuCode($item['sku_code'] ?? null);
                $sku->setSkuName($item['sku_name'] ?? '');
                $sku->setSpecValues($item['spec_values'] ?? null);
                $sku->setImage($item['image'] ?? null);
                $sku->setCostPrice((int) ($item['cost_price'] ?? 0));
                $sku->setMarketPrice((int) ($item['market_price'] ?? 0));
                $sku->setSalePrice((int) ($item['sale_price'] ?? 0));
                $sku->setStock((int) ($item['stock'] ?? 0));
                $sku->setWarningStock((int) ($item['warning_stock'] ?? 0));
                $sku->setWeight((float) ($item['weight'] ?? 0.0));
                $sku->setStatus($item['status'] ?? 'active');
                $skus[] = $sku;
            }
            $this->setSkus($skus);
        }

        // 处理属性
        $attrData = $input->getProductAttributes();
        if ($attrData !== null) {
            $attributes = [];
            foreach ($attrData as $item) {
                $attr = new ProductAttributeEntity();
                $attr->setAttributeName($item['attribute_name'] ?? '');
                $attr->setValue($item['value'] ?? '');
                $attributes[] = $attr;
            }
            $this->setAttributes($attributes);
        }

        // 同步价格范围
        $this->syncPriceRange();

        return $this;
    }

    /**
     * 更新行为方法：接收 DTO，内部组装设置值.
     */
    public function update(ProductInput $input): ProductChangeVo
    {
        $oldMinPrice = $this->minPrice;
        $oldMaxPrice = $this->maxPrice;
        $oldStatus = $this->status;

        // 更新基本信息
        if ($input->getProductCode() !== null) {
            $this->setProductCode($input->getProductCode());
        }
        if ($input->getCategoryId() !== null) {
            $this->setCategoryId($input->getCategoryId());
        }
        if ($input->getBrandId() !== null) {
            $this->setBrandId($input->getBrandId());
        }
        if ($input->getName() !== null) {
            $this->setName($input->getName());
        }
        if ($input->getSubTitle() !== null) {
            $this->setSubTitle($input->getSubTitle());
        }
        if ($input->getMainImage() !== null) {
            $this->setMainImage($input->getMainImage());
        }
        if ($input->getGalleryImages() !== null) {
            $this->setGalleryImages($input->getGalleryImages());
        }
        if ($input->getDescription() !== null) {
            $this->setDescription($input->getDescription());
        }
        if ($input->getDetailContent() !== null) {
            $this->setDetailContent($input->getDetailContent());
        }
        if ($input->getAttributes() !== null) {
            $this->setAttributesJson($input->getAttributes());
        }
        if ($input->getVirtualSales() !== null) {
            $this->setVirtualSales($input->getVirtualSales());
        }
        if ($input->getIsRecommend() !== null) {
            $this->setIsRecommend($input->getIsRecommend());
        }
        if ($input->getIsHot() !== null) {
            $this->setIsHot($input->getIsHot());
        }
        if ($input->getIsNew() !== null) {
            $this->setIsNew($input->getIsNew());
        }
        if ($input->getShippingTemplateId() !== null) {
            $this->setShippingTemplateId($input->getShippingTemplateId());
        }
        if ($input->getFreightType() !== null) {
            $this->setFreightType($input->getFreightType());
        }
        if ($input->getFlatFreightAmount() !== null) {
            $this->setFlatFreightAmount($input->getFlatFreightAmount());
        }
        if ($input->getSort() !== null) {
            $this->setSort($input->getSort());
        }
        if ($input->getStatus() !== null) {
            $this->setStatus($input->getStatus());
        }
        if ($input->getGallery() !== []) {
            $this->setGallery($input->getGallery());
        }

        // 处理 SKU
        $skuData = $input->getSkus();
        if ($skuData !== null) {
            $skus = [];
            foreach ($skuData as $item) {
                $sku = new ProductSkuEntity();
                if (isset($item['id'])) {
                    $sku->setId((int) $item['id']);
                }
                $sku->setSkuCode($item['sku_code'] ?? null);
                $sku->setSkuName($item['sku_name'] ?? '');
                $sku->setSpecValues($item['spec_values'] ?? null);
                $sku->setImage($item['image'] ?? null);
                $sku->setCostPrice((int) ($item['cost_price'] ?? 0));
                $sku->setMarketPrice((int) ($item['market_price'] ?? 0));
                $sku->setSalePrice((int) ($item['sale_price'] ?? 0));
                $sku->setStock((int) ($item['stock'] ?? 0));
                $sku->setWarningStock((int) ($item['warning_stock'] ?? 0));
                $sku->setWeight((float) ($item['weight'] ?? 0.0));
                $sku->setStatus($item['status'] ?? 'active');
                $skus[] = $sku;
            }
            $this->setSkus($skus);
        }

        // 处理属性
        $attrData = $input->getProductAttributes();
        if ($attrData !== null) {
            $attributes = [];
            foreach ($attrData as $item) {
                $attr = new ProductAttributeEntity();
                if (isset($item['id'])) {
                    $attr->setId((int) $item['id']);
                }
                $attr->setAttributeName($item['attribute_name'] ?? '');
                $attr->setValue($item['value'] ?? '');
                $attributes[] = $attr;
            }
            $this->setAttributes($attributes);
        }

        // 同步价格范围
        $this->syncPriceRange();

        // 检测变更
        $priceChanged = $oldMinPrice !== $this->minPrice || $oldMaxPrice !== $this->maxPrice;
        $statusChanged = $oldStatus !== $this->status;

        return new ProductChangeVo(
            productId: $this->id,
            priceChanged: $priceChanged,
            statusChanged: $statusChanged,
            stockChanged: $skuData !== null
        );
    }

    public function setProductCode(?string $code): void
    {
        $this->productCode = $code;
        $this->markDirty('product_code');
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
        $this->markDirty('category_id');
    }

    public function getBrandId(): ?int
    {
        return $this->brandId;
    }

    public function setBrandId(?int $brandId): void
    {
        $this->brandId = $brandId;
        $this->markDirty('brand_id');
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
        $this->markDirty('name');
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

    public function getMinPrice(): ?int
    {
        return $this->minPrice;
    }

    public function setMinPrice(?int $minPrice): void
    {
        $this->minPrice = $minPrice;
    }

    public function getMaxPrice(): ?int
    {
        return $this->maxPrice;
    }

    public function setMaxPrice(?int $maxPrice): void
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

    public function getFreightType(): ?string
    {
        return $this->freightType;
    }

    public function setFreightType(?string $freightType): void
    {
        $this->freightType = $freightType;
    }

    public function getFlatFreightAmount(): ?int
    {
        return $this->flatFreightAmount;
    }

    public function setFlatFreightAmount(?int $flatFreightAmount): void
    {
        $this->flatFreightAmount = $flatFreightAmount;
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
        $this->changeStatus($status);
        $this->markDirty('status');
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
        $this->markDirty('sort');
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
            throw new BusinessException(ResultCode::FAIL, '无效的商品状态');
        }

        $current = $this->status ?? ProductStatus::DRAFT->value;
        if ($current === $targetStatus) {
            $this->status = $targetStatus;
            return $this;
        }

        $allowed = self::STATUS_TRANSITIONS[$current] ?? [];
        if (! \in_array($targetStatus, $allowed, true)) {
            throw new BusinessException(
                ResultCode::FAIL,
                \sprintf('商品状态不允许从 %s 变更为 %s', $current, $targetStatus)
            );
        }

        $this->status = $targetStatus;
        $this->markDirty('status');
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
            $this->setMinPrice(0);
            $this->setMaxPrice(0);
            return;
        }

        $priceRange = PriceRangeVo::fromSkus($skus);
        $this->setMinPrice($priceRange->minPrice);
        $this->setMaxPrice($priceRange->maxPrice);
    }

    /**
     * 获取库存数据（用于领域事件）.
     *
     * @return array<int, array{sku_id: int, stock: int}>
     */
    public function getStockData(): array
    {
        $skus = $this->getSkus();
        if ($skus === null) {
            return [];
        }

        $stockData = [];
        foreach ($skus as $sku) {
            $skuId = $sku->getId();
            if ($skuId === null || $skuId <= 0) {
                continue;
            }
            $stockData[] = [
                'sku_id' => $skuId,
                'stock' => $sku->getStock(),
            ];
        }

        return $stockData;
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
            'freight_type' => $this->getFreightType(),
            'flat_freight_amount' => $this->getFlatFreightAmount(),
            'sort' => $this->getSort(),
            'status' => $this->getStatus(),
            'gallery' => $this->getGallery(),
            'delete_sku_ids' => $this->getDeleteSkuIds($model),
            'delete_attr_ids' => $this->getDeleteAttributeIds($model),
        ], static fn ($v) => $v !== null);
    }

    /**
     * 标记字段为已修改.
     */
    private function markDirty(string $field): void
    {
        $this->dirty[$field] = true;
    }

    private function assertCreateRequirements(): void
    {
        // Entity 层只验证业务规则
        // 格式验证（名称非空、分类ID有效等）应该在 Request 层完成

        $skus = $this->getSkus();
        if ($skus === null || $skus === []) {
            throw new BusinessException(ResultCode::FAIL, '请至少添加一个SKU');
        }

        $this->assertSkuIntegrity($skus);
    }

    private function assertUpdateRequirements(): void
    {
        // Entity 层只验证业务规则

        $skus = $this->getSkus();
        if ($skus === null) {
            return;
        }

        if ($skus === []) {
            throw new BusinessException(ResultCode::FAIL, 'SKU 列表不能为空');
        }

        $this->assertSkuIntegrity($skus);
    }

    /**
     * @param ProductSkuEntity[] $skus
     */
    private function assertSkuIntegrity(array $skus): void
    {
        foreach ($skus as $sku) {
            if (! $sku instanceof ProductSkuEntity) {
                throw new BusinessException(ResultCode::FAIL, 'SKU 数据必须通过实体传递');
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
            throw new BusinessException(ResultCode::FAIL, '最低价不能高于最高价');
        }
    }
}
