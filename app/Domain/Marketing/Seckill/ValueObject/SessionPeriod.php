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

namespace App\Domain\Marketing\Seckill\ValueObject;

use Carbon\Carbon;

/**
 * 场次时间段值对象.
 */
final class SessionPeriod
{
    private readonly Carbon $startTime;

    private readonly Carbon $endTime;

    public function __construct(Carbon|string $startTime, Carbon|string $endTime)
    {
        $this->startTime = $startTime instanceof Carbon ? $startTime : Carbon::parse($startTime);
        $this->endTime = $endTime instanceof Carbon ? $endTime : Carbon::parse($endTime);

        $this->validate();
    }

    public function getStartTime(): Carbon
    {
        return $this->startTime;
    }

    public function getEndTime(): Carbon
    {
        return $this->endTime;
    }

    public function getDurationInHours(): float
    {
        return $this->startTime->diffInHours($this->endTime, true);
    }

    public function getDurationInMinutes(): int
    {
        return $this->startTime->diffInMinutes($this->endTime);
    }

    public function isActive(): bool
    {
        $now = Carbon::now();
        return $now->gte($this->startTime) && $now->lte($this->endTime);
    }

    public function isPending(): bool
    {
        return Carbon::now()->lt($this->startTime);
    }

    public function isEnded(): bool
    {
        return Carbon::now()->gt($this->endTime);
    }

    public function overlaps(self $other): bool
    {
        return $this->startTime->lt($other->endTime) && $this->endTime->gt($other->startTime);
    }

    public function toArray(): array
    {
        return [
            'start_time' => $this->startTime->toDateTimeString(),
            'end_time' => $this->endTime->toDateTimeString(),
        ];
    }

    public function equals(self $other): bool
    {
        return $this->startTime->eq($other->startTime) && $this->endTime->eq($other->endTime);
    }

    private function validate(): void
    {
        // 业务规则：结束时间必须晚于开始时间
        if ($this->startTime->gte($this->endTime)) {
            throw new \InvalidArgumentException('场次开始时间必须早于结束时间');
        }
    }
}
