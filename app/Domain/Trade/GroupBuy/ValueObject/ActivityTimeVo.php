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

namespace App\Domain\Trade\GroupBuy\ValueObject;

use Carbon\Carbon;

final class ActivityTimeVo
{
    private readonly Carbon $startTime;

    private readonly Carbon $endTime;

    public function __construct(string $startTime, string $endTime)
    {
        $this->startTime = Carbon::parse($startTime);
        $this->endTime = Carbon::parse($endTime);
        $this->validate();
    }

    public function getStartTime(): string
    {
        return $this->startTime->format('Y-m-d H:i:s');
    }

    public function getEndTime(): string
    {
        return $this->endTime->format('Y-m-d H:i:s');
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

    public function getRemainingSeconds(): int
    {
        if ($this->isEnded()) {
            return 0;
        }
        return Carbon::now()->diffInSeconds($this->endTime, false);
    }

    private function validate(): void
    {
        if ($this->startTime->diffInDays($this->endTime) > 30) {
            throw new \DomainException('活动时长不能超过30天');
        }
    }
}
