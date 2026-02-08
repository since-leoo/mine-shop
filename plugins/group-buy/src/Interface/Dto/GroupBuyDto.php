<?php

declare(strict_types=1);

namespace Plugin\Since\GroupBuy\Interface\Dto;

use Hyperf\DTO\Annotation\Validation\Required;
use Plugin\Since\GroupBuy\Domain\Contract\GroupBuyCreateInput;
use Plugin\Since\GroupBuy\Domain\Contract\GroupBuyUpdateInput;

class GroupBuyDto implements GroupBuyCreateInput, GroupBuyUpdateInput
{
    public ?int $id = null;
    #[Required] public string $title = '';
    public ?string $description = null;
    #[Required] public int $product_id = 0;
    #[Required] public int $sku_id = 0;
    #[Required] public int $original_price = 0;
    #[Required] public int $group_price = 0;
    #[Required] public int $min_people = 2;
    #[Required] public int $max_people = 10;
    #[Required] public string $start_time = '';
    #[Required] public string $end_time = '';
    #[Required] public int $group_time_limit = 24;
    public string $status = 'pending';
    #[Required] public int $total_quantity = 0;
    public int $sort_order = 0;
    public bool $is_enabled = true;
    public ?array $rules = null;
    public ?array $images = null;
    public ?string $remark = null;

    public function getId(): int { return $this->id ?? 0; }
    public function getTitle(): string { return $this->title; }
    public function getDescription(): ?string { return $this->description; }
    public function getProductId(): int { return $this->product_id; }
    public function getSkuId(): int { return $this->sku_id; }
    public function getOriginalPrice(): int { return $this->original_price; }
    public function getGroupPrice(): int { return $this->group_price; }
    public function getMinPeople(): int { return $this->min_people; }
    public function getMaxPeople(): int { return $this->max_people; }
    public function getStartTime(): string { return $this->start_time; }
    public function getEndTime(): string { return $this->end_time; }
    public function getGroupTimeLimit(): int { return $this->group_time_limit; }
    public function getStatus(): string { return $this->status; }
    public function getTotalQuantity(): int { return $this->total_quantity; }
    public function getSortOrder(): int { return $this->sort_order; }
    public function getIsEnabled(): bool { return $this->is_enabled; }
    public function getRules(): ?array { return $this->rules; }
    public function getImages(): ?array { return $this->images; }
    public function getRemark(): ?string { return $this->remark; }
}
