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

namespace App\Interface\Admin\DTO\Product;

use App\Domain\Product\Contract\ProductInput;

/**
 * 商品 DTO.
 */
class ProductDto implements ProductInput
{
    public ?int $id = null;

    public ?string $product_code = null;

    public ?int $category_id = null;

    public ?int $brand_id = null;

    public ?string $name = null;

    public ?string $sub_title = null;

    public ?string $main_image = null;

    public ?array $gallery_images = null;

    public ?string $description = null;

    public ?string $detail_content = null;

    public ?array $attributes = null;

    public ?float $min_price = null;

    public ?float $max_price = null;

    public ?int $virtual_sales = null;

    public ?int $real_sales = null;

    public ?bool $is_recommend = null;

    public ?bool $is_hot = null;

    public ?bool $is_new = null;

    public ?int $shipping_template_id = null;

    public ?int $sort = null;

    public ?string $status = null;

    public ?array $skus = null;

    public ?array $product_attributes = null;

    public array $gallery = [];

    public function getId(): int
    {
        return $this->id ?? 0;
    }

    public function getProductCode(): ?string
    {
        return $this->product_code;
    }

    public function getCategoryId(): ?int
    {
        return $this->category_id;
    }

    public function getBrandId(): ?int
    {
        return $this->brand_id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getSubTitle(): ?string
    {
        return $this->sub_title;
    }

    public function getMainImage(): ?string
    {
        return $this->main_image;
    }

    public function getGalleryImages(): ?array
    {
        return $this->gallery_images;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDetailContent(): ?string
    {
        return $this->detail_content;
    }

    public function getAttributes(): ?array
    {
        return $this->attributes;
    }

    public function getMinPrice(): ?float
    {
        return $this->min_price;
    }

    public function getMaxPrice(): ?float
    {
        return $this->max_price;
    }

    public function getVirtualSales(): ?int
    {
        return $this->virtual_sales;
    }

    public function getRealSales(): ?int
    {
        return $this->real_sales;
    }

    public function getIsRecommend(): ?bool
    {
        return $this->is_recommend;
    }

    public function getIsHot(): ?bool
    {
        return $this->is_hot;
    }

    public function getIsNew(): ?bool
    {
        return $this->is_new;
    }

    public function getShippingTemplateId(): ?int
    {
        return $this->shipping_template_id;
    }

    public function getSort(): ?int
    {
        return $this->sort;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getSkus(): ?array
    {
        return $this->skus;
    }

    public function getProductAttributes(): ?array
    {
        return $this->product_attributes;
    }

    public function getGallery(): array
    {
        return $this->gallery;
    }
}
