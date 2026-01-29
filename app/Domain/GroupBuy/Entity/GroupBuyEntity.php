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

namespace App\Domain\GroupBuy\Entity;

/**
 * 团购活动实体.
 */
final class GroupBuyEntity
{
    public function __construct(
        private int $id = 0,
        private ?string $title = null,
        private ?string $description = null,
        private ?int $productId = null,
        private ?int $skuId = null,
        private ?float $originalPrice = null,
        private ?float $groupPrice = null,
        private ?int $minPeople = null,
        private ?int $maxPeople = null,
        private ?string $startTime = null,
        private ?string $endTime = null,
        private ?int $groupTimeLimit = null,
        private ?string $status = null,
        private ?int $totalQuantity = null,
        private ?int $soldQuantity = null,
        private ?int $groupCount = null,
        private ?int $successGroupCount = null,
        private ?int $sortOrder = null,
        private ?bool $isEnabled = null,
        private ?array $rules = null,
        private ?array $images = null,
        private ?string $remark = null
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function getSkuId(): ?int
    {
        return $this->skuId;
    }

    public function getOriginalPrice(): ?float
    {
        return $this->originalPrice;
    }

    public function getGroupPrice(): ?float
    {
        return $this->groupPrice;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
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
            'title' => $this->title,
            'description' => $this->description,
            'product_id' => $this->productId,
            'sku_id' => $this->skuId,
            'original_price' => $this->originalPrice,
            'group_price' => $this->groupPrice,
            'min_people' => $this->minPeople,
            'max_people' => $this->maxPeople,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'group_time_limit' => $this->groupTimeLimit,
            'status' => $this->status,
            'total_quantity' => $this->totalQuantity,
            'sold_quantity' => $this->soldQuantity,
            'group_count' => $this->groupCount,
            'success_group_count' => $this->successGroupCount,
            'sort_order' => $this->sortOrder,
            'is_enabled' => $this->isEnabled,
            'rules' => $this->rules,
            'images' => $this->images,
            'remark' => $this->remark,
        ], static fn ($v) => $v !== null);
    }
}
