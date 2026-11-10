<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\Dto;

use App\Domain\Catalog\Product\Contract\ProductInput;

/** 应用端口的不可变商品输入；字段与核心商品契约一致。 */
final readonly class CommerceProductData implements ProductInput
{
    public function __construct(private array $data) {}
    public function getId(): int { return $this->data['id'] ?? 0; }
    public function getProductCode(): ?string { return $this->data['product_code'] ?? null; }
    public function getCategoryId(): ?int { return $this->data['category_id'] ?? null; }
    public function getBrandId(): ?int { return $this->data['brand_id'] ?? null; }
    public function getName(): ?string { return $this->data['name'] ?? null; }
    public function getSubTitle(): ?string { return $this->data['sub_title'] ?? null; }
    public function getMainImage(): ?string { return $this->data['main_image'] ?? null; }
    public function getGalleryImages(): ?array { return $this->data['gallery_images'] ?? null; }
    public function getDescription(): ?string { return $this->data['description'] ?? null; }
    public function getDetailContent(): ?string { return $this->data['detail_content'] ?? null; }
    public function getAttributes(): ?array { return $this->data['attributes'] ?? null; }
    public function getMinPrice(): ?float { return $this->data['min_price'] ?? null; }
    public function getMaxPrice(): ?float { return $this->data['max_price'] ?? null; }
    public function getVirtualSales(): ?int { return $this->data['virtual_sales'] ?? null; }
    public function getRealSales(): ?int { return $this->data['real_sales'] ?? null; }
    public function getIsRecommend(): ?bool { return $this->data['is_recommend'] ?? null; }
    public function getIsHot(): ?bool { return $this->data['is_hot'] ?? null; }
    public function getIsNew(): ?bool { return $this->data['is_new'] ?? null; }
    public function getShippingTemplateId(): ?int { return $this->data['shipping_template_id'] ?? null; }
    public function getFreightType(): ?string { return $this->data['freight_type'] ?? null; }
    public function getFlatFreightAmount(): ?int { return $this->data['flat_freight_amount'] ?? null; }
    public function getSort(): ?int { return $this->data['sort'] ?? null; }
    public function getStatus(): ?string { return $this->data['status'] ?? null; }
    public function getSkus(): ?array { return $this->data['skus'] ?? null; }
    public function getProductAttributes(): ?array { return $this->data['product_attributes'] ?? null; }
    public function getGallery(): array { return $this->data['gallery'] ?? []; }
}
