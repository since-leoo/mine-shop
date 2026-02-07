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

namespace App\Domain\Product\Contract;

/**
 * 商品输入契约接口.
 */
interface ProductInput
{
    public function getId(): int;

    public function getProductCode(): ?string;

    public function getCategoryId(): ?int;

    public function getBrandId(): ?int;

    public function getName(): ?string;

    public function getSubTitle(): ?string;

    public function getMainImage(): ?string;

    /**
     * @return null|string[]
     */
    public function getGalleryImages(): ?array;

    public function getDescription(): ?string;

    public function getDetailContent(): ?string;

    /**
     * @return null|array<string, mixed>
     */
    public function getAttributes(): ?array;

    public function getMinPrice(): ?float;

    public function getMaxPrice(): ?float;

    public function getVirtualSales(): ?int;

    public function getRealSales(): ?int;

    public function getIsRecommend(): ?bool;

    public function getIsHot(): ?bool;

    public function getIsNew(): ?bool;

    public function getShippingTemplateId(): ?int;

    public function getFreightType(): ?string;

    public function getFlatFreightAmount(): ?int;

    public function getSort(): ?int;

    public function getStatus(): ?string;

    /**
     * @return null|array<int, array<string, mixed>>
     */
    public function getSkus(): ?array;

    /**
     * @return null|array<int, array<string, mixed>>
     */
    public function getProductAttributes(): ?array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getGallery(): array;
}
