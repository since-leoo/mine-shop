<?php

declare(strict_types=1);

namespace App\Domain\Trade\Seckill\Contract;

interface SeckillSessionInput
{
    public function getId(): int;

    public function getActivityId(): ?int;

    public function getStartTime(): ?string;

    public function getEndTime(): ?string;

    public function getStatus(): ?string;

    public function getMaxQuantityPerUser(): ?int;

    public function getTotalQuantity(): ?int;

    public function getSortOrder(): ?int;

    public function getRules(): ?array;

    public function getRemark(): ?string;
}
