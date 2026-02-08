<?php

declare(strict_types=1);

namespace Plugin\Since\Seckill\Domain\Event;

final class SeckillActivityDeletedEvent
{
    public function __construct(private readonly int $activityId) {}
    public function getActivityId(): int { return $this->activityId; }
}
