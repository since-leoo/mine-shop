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

namespace App\Domain\Marketing\Seckill\Event;

use App\Domain\Marketing\Seckill\Enum\SeckillStatus;

/**
 * 秒杀活动状态变更事件.
 */
final class SeckillActivityStatusChangedEvent
{
    public function __construct(
        private readonly int $activityId,
        private readonly SeckillStatus $oldStatus,
        private readonly SeckillStatus $newStatus
    ) {}

    public function getActivityId(): int
    {
        return $this->activityId;
    }

    public function getOldStatus(): SeckillStatus
    {
        return $this->oldStatus;
    }

    public function getNewStatus(): SeckillStatus
    {
        return $this->newStatus;
    }

    public function isActivated(): bool
    {
        return $this->newStatus === SeckillStatus::ACTIVE;
    }

    public function isDeactivated(): bool
    {
        return $this->oldStatus === SeckillStatus::ACTIVE && $this->newStatus !== SeckillStatus::ACTIVE;
    }
}
