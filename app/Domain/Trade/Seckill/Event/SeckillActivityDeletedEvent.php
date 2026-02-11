<?php

declare(strict_types=1);

namespace App\Domain\Trade\Seckill\Event;

final class SeckillActivityDeletedEvent
{
    public function __construct(private readonly int $activityId) {}

    public function getActivityId(): int
    {
        return $this->activityId;
    }
}
