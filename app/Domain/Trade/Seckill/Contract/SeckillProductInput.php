<?php

declare(strict_types=1);

namespace App\Domain\Trade\Seckill\Contract;

interface SeckillProductInput
{
    public function getId(): int;

    public function getActivityId(): ?int;

    public function getSessionId(): ?int;

    public function getProductId(): ?int;

    public function getProductSkuId(): ?int;

    public function getOriginalPrice(): ?int;

    public function getSeckillPrice(): ?int;

    public function getQuantity(): ?int;

    public function getMaxQuantityPerUser(): ?int;

    public function getSortOrder(): ?int;
}
