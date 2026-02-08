<?php

declare(strict_types=1);

namespace Plugin\Since\Seckill\Domain\Event;

use Plugin\Since\Seckill\Domain\Enum\SeckillStatus;

final class SeckillActivityStatusChangedEvent
{
    public function __construct(private readonly int $activityId, private readonly SeckillStatus $oldStatus, private readonly SeckillStatus $newStatus) {}
    public function getActivityId(): int { return $this->activityId; }
    public function getOldStatus(): SeckillStatus { return $this->oldStatus; }
    public function getNewStatus(): SeckillStatus { return $this->newStatus; }
    public function isActivated(): bool { return $this->newStatus === SeckillStatus::ACTIVE; }
    public function isDeactivated(): bool { return $this->oldStatus === SeckillStatus::ACTIVE && $this->newStatus !== SeckillStatus::ACTIVE; }
}
