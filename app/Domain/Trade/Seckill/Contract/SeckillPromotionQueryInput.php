<?php

declare(strict_types=1);

namespace App\Domain\Trade\Seckill\Contract;

interface SeckillPromotionQueryInput
{
    public function getActivityId(): ?int;

    public function getSessionId(): ?int;

    public function getLimit(): int;
}
