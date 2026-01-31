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

namespace App\Domain\SystemSetting\ValueObject;

/**
 * 会员配置值对象.
 */
final class MemberSetting
{
    /**
     * @param array<int, array<string, mixed>> $vipLevels
     */
    public function __construct(
        private readonly bool $enableGrowth,
        private readonly int $registerPoints,
        private readonly int $signInReward,
        private readonly int $inviteReward,
        private readonly int $pointsExpireMonths,
        private readonly array $vipLevels,
    ) {}

    public function enableGrowth(): bool
    {
        return $this->enableGrowth;
    }

    public function registerPoints(): int
    {
        return $this->registerPoints;
    }

    public function signInReward(): int
    {
        return $this->signInReward;
    }

    public function inviteReward(): int
    {
        return $this->inviteReward;
    }

    public function pointsExpireMonths(): int
    {
        return $this->pointsExpireMonths;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function vipLevels(): array
    {
        return $this->vipLevels;
    }
}
