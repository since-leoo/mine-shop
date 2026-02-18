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

namespace App\Domain\Trade\Seckill\Event;

final class SeckillActivityEnabledEvent
{
    public function __construct(private readonly int $activityId, private readonly bool $isEnabled) {}

    public function getActivityId(): int
    {
        return $this->activityId;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function isDisabled(): bool
    {
        return ! $this->isEnabled;
    }
}
