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

namespace App\Interface\Admin\Dto\Review;

use App\Domain\Trade\Review\Contract\ReviewInput;

/**
 * 评价DTO.
 */
final class ReviewDto implements ReviewInput
{
    public ?int $id = null;

    public ?int $orderId = null;

    public ?int $orderItemId = null;

    public ?int $productId = null;

    public ?int $skuId = null;

    public ?int $memberId = null;

    public ?int $rating = null;

    public ?string $content = null;

    public ?array $images = null;

    public ?bool $isAnonymous = null;

    public function getId(): int
    {
        return $this->id ?? 0;
    }

    public function getOrderId(): ?int
    {
        return $this->orderId;
    }

    public function getOrderItemId(): ?int
    {
        return $this->orderItemId;
    }

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function getSkuId(): ?int
    {
        return $this->skuId;
    }

    public function getMemberId(): ?int
    {
        return $this->memberId;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function getImages(): ?array
    {
        return $this->images;
    }

    public function getIsAnonymous(): ?bool
    {
        return $this->isAnonymous;
    }
}
