<?php

declare(strict_types=1);

namespace Plugin\Since\GroupBuy\Domain\Entity;

use Plugin\Since\GroupBuy\Domain\Contract\GroupBuyCreateInput;
use Plugin\Since\GroupBuy\Domain\Contract\GroupBuyUpdateInput;
use Plugin\Since\GroupBuy\Domain\ValueObject\ActivityTimeVo;
use Plugin\Since\GroupBuy\Domain\ValueObject\GroupPeopleVo;
use Plugin\Since\GroupBuy\Domain\ValueObject\PriceVo;

/**
 * 团购活动实体.
 */
final class GroupBuyEntity
{
    private int $id = 0;
    private string $title = '';
    private ?string $description = null;
    private int $productId = 0;
    private int $skuId = 0;
    private int $originalPrice = 0;
    private int $groupPrice = 0;
    private int $minPeople = 2;
    private int $maxPeople = 10;
    private string $startTime = '';
    private string $endTime = '';
    private int $groupTimeLimit = 24;
    private string $status = 'pending';
    private int $totalQuantity = 0;
    private int $soldQuantity = 0;
    private int $groupCount = 0;
    private int $successGroupCount = 0;
    private int $sortOrder = 0;
    private bool $isEnabled = true;
    private ?array $rules = null;
    private ?array $images = null;
    private ?string $remark = null;
    /** @var array<string, bool> */
    private array $dirty = [];

    public function create(GroupBuyCreateInput $dto): self
    {
        $this->setTitle($dto->getTitle());
        $this->setDescription($dto->getDescription());
        $this->setProductId($dto->getProductId());
        $this->setSkuId($dto->getSkuId());
        $priceVo = new PriceVo($dto->getOriginalPrice(), $dto->getGroupPrice());
        $this->setOriginalPrice($priceVo->getOriginalPrice());
        $this->setGroupPrice($priceVo->getGroupPrice());
        $peopleVo = new GroupPeopleVo($dto->getMinPeople(), $dto->getMaxPeople());
        $this->setMinPeople($peopleVo->getMinPeople());
        $this->setMaxPeople($peopleVo->getMaxPeople());
        $timeVo = new ActivityTimeVo($dto->getStartTime(), $dto->getEndTime());
        $this->setStartTime($timeVo->getStartTime());
        $this->setEndTime($timeVo->getEndTime());
        $this->setGroupTimeLimit($dto->getGroupTimeLimit());
        $this->setStatus($dto->getStatus());
        $this->setTotalQuantity($dto->getTotalQuantity());
        $this->setSoldQuantity(0);
        $this->setGroupCount(0);
        $this->setSuccessGroupCount(0);
        $this->setSortOrder($dto->getSortOrder());
        $this->setIsEnabled($dto->getIsEnabled());
        $this->setRules($dto->getRules());
        $this->setImages($dto->getImages());
        $this->setRemark($dto->getRemark());
        return $this;
    }

    public function update(GroupBuyUpdateInput $dto): self
    {
        $this->setTitle($dto->getTitle());
        $this->setDescription($dto->getDescription());
        $this->setProductId($dto->getProductId());
        $this->setSkuId($dto->getSkuId());
        $priceVo = new PriceVo($dto->getOriginalPrice(), $dto->getGroupPrice());
        $this->setOriginalPrice($priceVo->getOriginalPrice());
        $this->setGroupPrice($priceVo->getGroupPrice());
        $peopleVo = new GroupPeopleVo($dto->getMinPeople(), $dto->getMaxPeople());
        $this->setMinPeople($peopleVo->getMinPeople());
        $this->setMaxPeople($peopleVo->getMaxPeople());
        $timeVo = new ActivityTimeVo($dto->getStartTime(), $dto->getEndTime());
        $this->setStartTime($timeVo->getStartTime());
        $this->setEndTime($timeVo->getEndTime());
        $this->setGroupTimeLimit($dto->getGroupTimeLimit());
        if ($dto->getStatus() !== null) {
            $this->setStatus($dto->getStatus());
        }
        $this->setTotalQuantity($dto->getTotalQuantity());
        $this->setSortOrder($dto->getSortOrder());
        $this->setIsEnabled($dto->getIsEnabled());
        $this->setRules($dto->getRules());
        $this->setImages($dto->getImages());
        $this->setRemark($dto->getRemark());
        return $this;
    }

    public function enable(): self
    {
        if (! $this->canEnable()) {
            throw new \DomainException('活动不满足启用条件');
        }
        $this->setIsEnabled(true);
        return $this;
    }

    public function disable(): self
    {
        $this->setIsEnabled(false);
        return $this;
    }

    public function start(): self
    {
        if ($this->status !== 'pending') {
            throw new \DomainException('只有待开始的活动才能启动');
        }
        $this->setStatus('active');
        return $this;
    }

    public function end(): self
    {
        if ($this->status === 'ended') {
            throw new \DomainException('活动已经结束');
        }
        $this->setStatus('ended');
        return $this;
    }

    public function increaseSoldQuantity(int $quantity): self
    {
        if ($quantity <= 0) {
            throw new \DomainException('销量增加数量必须大于0');
        }
        $newSoldQuantity = $this->soldQuantity + $quantity;
        if ($newSoldQuantity > $this->totalQuantity) {
            throw new \DomainException('库存不足');
        }
        $this->setSoldQuantity($newSoldQuantity);
        if ($newSoldQuantity >= $this->totalQuantity) {
            $this->setStatus('sold_out');
        }
        return $this;
    }

    public function increaseGroupCount(): self
    {
        $this->setGroupCount($this->groupCount + 1);
        return $this;
    }

    public function increaseSuccessGroupCount(): self
    {
        $this->setSuccessGroupCount($this->successGroupCount + 1);
        return $this;
    }

    public function canJoin(): bool
    {
        if (! $this->isEnabled) {
            return false;
        }
        if ($this->status !== 'active') {
            return false;
        }
        if ($this->soldQuantity >= $this->totalQuantity) {
            return false;
        }
        $timeVo = new ActivityTimeVo($this->startTime, $this->endTime);
        return $timeVo->isActive();
    }

    // Getters & Setters
    public function getId(): int { return $this->id; }
    public function setId(int $id): self { $this->id = $id; return $this; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self { $title = trim($title); if ($title === '') { throw new \DomainException('活动标题不能为空'); } $this->title = $title; $this->markDirty('title'); return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; $this->markDirty('description'); return $this; }
    public function getProductId(): int { return $this->productId; }
    public function setProductId(int $productId): self { $this->productId = $productId; $this->markDirty('product_id'); return $this; }
    public function getSkuId(): int { return $this->skuId; }
    public function setSkuId(int $skuId): self { $this->skuId = $skuId; $this->markDirty('sku_id'); return $this; }
    public function getOriginalPrice(): int { return $this->originalPrice; }
    public function setOriginalPrice(int $originalPrice): self { $this->originalPrice = $originalPrice; $this->markDirty('original_price'); return $this; }
    public function getGroupPrice(): int { return $this->groupPrice; }
    public function setGroupPrice(int $groupPrice): self { $this->groupPrice = $groupPrice; $this->markDirty('group_price'); return $this; }
    public function getMinPeople(): int { return $this->minPeople; }
    public function setMinPeople(int $minPeople): self { $this->minPeople = $minPeople; $this->markDirty('min_people'); return $this; }
    public function getMaxPeople(): int { return $this->maxPeople; }
    public function setMaxPeople(int $maxPeople): self { $this->maxPeople = $maxPeople; $this->markDirty('max_people'); return $this; }
    public function getStartTime(): string { return $this->startTime; }
    public function setStartTime(string $startTime): self { $this->startTime = $startTime; $this->markDirty('start_time'); return $this; }
    public function getEndTime(): string { return $this->endTime; }
    public function setEndTime(string $endTime): self { $this->endTime = $endTime; $this->markDirty('end_time'); return $this; }
    public function getGroupTimeLimit(): int { return $this->groupTimeLimit; }
    public function setGroupTimeLimit(int $groupTimeLimit): self { $this->groupTimeLimit = $groupTimeLimit; $this->markDirty('group_time_limit'); return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; $this->markDirty('status'); return $this; }
    public function getTotalQuantity(): int { return $this->totalQuantity; }
    public function setTotalQuantity(int $totalQuantity): self { $this->totalQuantity = $totalQuantity; $this->markDirty('total_quantity'); return $this; }
    public function getSoldQuantity(): int { return $this->soldQuantity; }
    public function setSoldQuantity(int $soldQuantity): self { $this->soldQuantity = $soldQuantity; $this->markDirty('sold_quantity'); return $this; }
    public function getGroupCount(): int { return $this->groupCount; }
    public function setGroupCount(int $groupCount): self { $this->groupCount = $groupCount; $this->markDirty('group_count'); return $this; }
    public function getSuccessGroupCount(): int { return $this->successGroupCount; }
    public function setSuccessGroupCount(int $successGroupCount): self { $this->successGroupCount = $successGroupCount; $this->markDirty('success_group_count'); return $this; }
    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $sortOrder): self { $this->sortOrder = $sortOrder; $this->markDirty('sort_order'); return $this; }
    public function getIsEnabled(): bool { return $this->isEnabled; }
    public function setIsEnabled(bool $isEnabled): self { $this->isEnabled = $isEnabled; $this->markDirty('is_enabled'); return $this; }
    public function getRules(): ?array { return $this->rules; }
    public function setRules(?array $rules): self { $this->rules = $rules; $this->markDirty('rules'); return $this; }
    public function getImages(): ?array { return $this->images; }
    public function setImages(?array $images): self { $this->images = $images; $this->markDirty('images'); return $this; }
    public function getRemark(): ?string { return $this->remark; }
    public function setRemark(?string $remark): self { $this->remark = $remark; $this->markDirty('remark'); return $this; }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = [
            'title' => $this->title, 'description' => $this->description,
            'product_id' => $this->productId, 'sku_id' => $this->skuId,
            'original_price' => $this->originalPrice, 'group_price' => $this->groupPrice,
            'min_people' => $this->minPeople, 'max_people' => $this->maxPeople,
            'start_time' => $this->startTime, 'end_time' => $this->endTime,
            'group_time_limit' => $this->groupTimeLimit, 'status' => $this->status,
            'total_quantity' => $this->totalQuantity, 'sold_quantity' => $this->soldQuantity,
            'group_count' => $this->groupCount, 'success_group_count' => $this->successGroupCount,
            'sort_order' => $this->sortOrder, 'is_enabled' => $this->isEnabled,
            'rules' => $this->rules, 'images' => $this->images, 'remark' => $this->remark,
        ];
        if ($this->dirty === []) {
            return array_filter($data, static fn ($value) => $value !== null);
        }
        return array_filter($data, function ($value, string $field) {
            return isset($this->dirty[$field]) && $value !== null;
        }, \ARRAY_FILTER_USE_BOTH);
    }

    private function canEnable(): bool
    {
        if (empty($this->title) || $this->productId === 0 || $this->skuId === 0) {
            return false;
        }
        return $this->totalQuantity > 0;
    }

    private function markDirty(string $field): void
    {
        $this->dirty[$field] = true;
    }
}
