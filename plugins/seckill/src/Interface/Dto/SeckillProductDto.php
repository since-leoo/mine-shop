<?php

declare(strict_types=1);

namespace Plugin\Since\Seckill\Interface\Dto;

use Plugin\Since\Seckill\Domain\Contract\SeckillProductInput;

final class SeckillProductDto implements SeckillProductInput
{
    public ?int $id = null;
    public ?int $activity_id = null;
    public ?int $session_id = null;
    public ?int $product_id = null;
    public ?int $product_sku_id = null;
    public ?int $original_price = null;
    public ?int $seckill_price = null;
    public ?int $quantity = null;
    public ?int $max_quantity_per_user = null;
    public ?int $sort_order = null;

    public function getId(): int { return $this->id ?? 0; }
    public function getActivityId(): ?int { return $this->activity_id; }
    public function getSessionId(): ?int { return $this->session_id; }
    public function getProductId(): ?int { return $this->product_id; }
    public function getProductSkuId(): ?int { return $this->product_sku_id; }
    public function getOriginalPrice(): ?int { return $this->original_price; }
    public function getSeckillPrice(): ?int { return $this->seckill_price; }
    public function getQuantity(): ?int { return $this->quantity; }
    public function getMaxQuantityPerUser(): ?int { return $this->max_quantity_per_user; }
    public function getSortOrder(): ?int { return $this->sort_order; }
}
