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

/**
 * 秒杀活动删除事件.
 */
final class SeckillActivityDeletedEvent
{
    public function __construct(
        private readonly int $activityId
    ) {}

    public function getActivityId(): int
    {
        return $this->activityId;
    }
}
