<?php

declare(strict_types=1);

namespace Plugin\Since\Seckill\Domain\Event;

final class SeckillActivityEnabledEvent
{
    public function __construct(private readonly int $activityId, private readonly bool $isEnabled) {}
    public function getActivityId(): int { return $this->activityId; }
    public function isEnabled(): bool { return $this->isEnabled; }
    public function isDisabled(): bool { return !$this->isEnabled; }
}
