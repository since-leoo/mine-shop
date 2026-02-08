<?php

declare(strict_types=1);

namespace Plugin\Since\Seckill\Domain\Event;

use Plugin\Since\Seckill\Domain\Entity\SeckillActivityEntity;

final class SeckillActivityCreatedEvent
{
    public function __construct(private readonly SeckillActivityEntity $activity, private readonly int $activityId) {}
    public function getActivity(): SeckillActivityEntity { return $this->activity; }
    public function getActivityId(): int { return $this->activityId; }
}
