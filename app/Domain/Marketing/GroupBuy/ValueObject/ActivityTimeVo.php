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

namespace App\Domain\Marketing\GroupBuy\ValueObject;

use Carbon\Carbon;

/**
 * 活动时间值对象.
 */
final class ActivityTimeVo
{
    private readonly Carbon $startTime;

    private readonly Carbon $endTime;

    public function __construct(
        string $startTime,
        string $endTime
    ) {
        $this->startTime = Carbon::parse($startTime);
        $this->endTime = Carbon::parse($endTime);
        $this->validate();
    }

    /**
     * 获取开始时间.
     */
    public function getStartTime(): string
    {
        return $this->startTime->format('Y-m-d H:i:s');
    }

    /**
     * 获取结束时间.
     */
    public function getEndTime(): string
    {
        return $this->endTime->format('Y-m-d H:i:s');
    }

    /**
     * 检查活动是否进行中.
     */
    public function isActive(): bool
    {
        $now = Carbon::now();
        return $now->gte($this->startTime) && $now->lte($this->endTime);
    }

    /**
     * 检查活动是否未开始.
     */
    public function isPending(): bool
    {
        return Carbon::now()->lt($this->startTime);
    }

    /**
     * 检查活动是否已结束.
     */
    public function isEnded(): bool
    {
        return Carbon::now()->gt($this->endTime);
    }

    /**
     * 获取活动剩余时间（秒）.
     */
    public function getRemainingSeconds(): int
    {
        if ($this->isEnded()) {
            return 0;
        }

        return Carbon::now()->diffInSeconds($this->endTime, false);
    }

    /**
     * 验证活动时间业务规则.
     */
    private function validate(): void
    {
        // 业务规则：活动时长不能超过30天
        if ($this->startTime->diffInDays($this->endTime) > 30) {
            throw new \DomainException('活动时长不能超过30天');
        }
    }
}
