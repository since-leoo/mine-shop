<?php

declare(strict_types=1);

namespace Plugin\Since\GroupBuy\Domain\Contract;

/**
 * 团购活动创建输入契约.
 */
interface GroupBuyCreateInput
{
    public function getTitle(): string;

    public function getDescription(): ?string;

    public function getProductId(): int;

    public function getSkuId(): int;

    public function getOriginalPrice(): int;

    public function getGroupPrice(): int;

    public function getMinPeople(): int;

    public function getMaxPeople(): int;

    public function getStartTime(): string;

    public function getEndTime(): string;

    public function getGroupTimeLimit(): int;

    public function getStatus(): string;

    public function getTotalQuantity(): int;

    public function getSortOrder(): int;

    public function getIsEnabled(): bool;

    public function getRules(): ?array;

    public function getImages(): ?array;

    public function getRemark(): ?string;
}
