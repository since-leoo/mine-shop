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

namespace App\Domain\Seckill\Entity;

/**
 * 秒杀商品实体.
 */
final class SeckillProductEntity
{
    public function __construct(
        private int $id = 0,
        private ?int $activityId = null,
        private ?int $sessionId = null,
        private ?int $productId = null,
        private ?int $productSkuId = null,
        private ?float $originalPrice = null,
        private ?float $seckillPrice = null,
        private ?int $quantity = null,
        private ?int $soldQuantity = null,
        private ?int $maxQuantityPerUser = null,
        private ?int $sortOrder = null,
        private ?bool $isEnabled = null
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getActivityId(): ?int
    {
        return $this->activityId;
    }

    public function setActivityId(?int $activityId): self
    {
        $this->activityId = $activityId;
        return $this;
    }

    public function getSessionId(): ?int
    {
        return $this->sessionId;
    }

    public function setSessionId(?int $sessionId): self
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function getProductSkuId(): ?int
    {
        return $this->productSkuId;
    }

    public function getOriginalPrice(): ?float
    {
        return $this->originalPrice;
    }

    public function getSeckillPrice(): ?float
    {
        return $this->seckillPrice;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function getSoldQuantity(): ?int
    {
        return $this->soldQuantity;
    }

    public function getMaxQuantityPerUser(): ?int
    {
        return $this->maxQuantityPerUser;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function getIsEnabled(): ?bool
    {
        return $this->isEnabled;
    }

    public function setIsEnabled(?bool $isEnabled): self
    {
        $this->isEnabled = $isEnabled;
        return $this;
    }

    /**
     * 转换为数组（用于持久化）.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'activity_id' => $this->activityId,
            'session_id' => $this->sessionId,
            'product_id' => $this->productId,
            'product_sku_id' => $this->productSkuId,
            'original_price' => $this->originalPrice,
            'seckill_price' => $this->seckillPrice,
            'quantity' => $this->quantity,
            'sold_quantity' => $this->soldQuantity,
            'max_quantity_per_user' => $this->maxQuantityPerUser,
            'sort_order' => $this->sortOrder,
            'is_enabled' => $this->isEnabled,
        ], static fn ($v) => $v !== null);
    }
}
