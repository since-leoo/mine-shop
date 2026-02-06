<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace App\Domain\Seckill\Event;

use App\Domain\Seckill\Entity\SeckillActivityEntity;

/**
 * 秒杀活动更新事件.
 */
final class SeckillActivityUpdatedEvent
{
    public function __construct(
        private readonly SeckillActivityEntity $activity,
        private readonly int $activityId,
        private readonly array $changedFields = []
    ) {}

    public function getActivity(): SeckillActivityEntity
    {
        return $this->activity;
    }

    public function getActivityId(): int
    {
        return $this->activityId;
    }

    public function getChangedFields(): array
    {
        return $this->changedFields;
    }

    public function hasFieldChanged(string $field): bool
    {
        return \in_array($field, $this->changedFields, true);
    }
}
