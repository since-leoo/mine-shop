<?php

declare(strict_types=1);

namespace Plugin\Since\Seckill\Domain\Event;

use Plugin\Since\Seckill\Domain\Entity\SeckillActivityEntity;

final class SeckillActivityUpdatedEvent
{
    public function __construct(private readonly SeckillActivityEntity $activity, private readonly int $activityId, private readonly array $changedFields = []) {}
    public function getActivity(): SeckillActivityEntity { return $this->activity; }
    public function getActivityId(): int { return $this->activityId; }
    public function getChangedFields(): array { return $this->changedFields; }
    public function hasFieldChanged(string $field): bool { return \in_array($field, $this->changedFields, true); }
}
