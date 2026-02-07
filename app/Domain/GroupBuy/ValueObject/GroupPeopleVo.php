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

namespace App\Domain\GroupBuy\ValueObject;

/**
 * 成团人数值对象.
 */
final class GroupPeopleVo
{
    public function __construct(
        private readonly int $minPeople,
        private readonly int $maxPeople
    ) {
        $this->validate();
    }

    /**
     * 获取最少成团人数.
     */
    public function getMinPeople(): int
    {
        return $this->minPeople;
    }

    /**
     * 获取最多成团人数.
     */
    public function getMaxPeople(): int
    {
        return $this->maxPeople;
    }

    /**
     * 检查人数是否满足成团条件.
     */
    public function canFormGroup(int $currentPeople): bool
    {
        return $currentPeople >= $this->minPeople && $currentPeople <= $this->maxPeople;
    }

    /**
     * 验证成团人数业务规则.
     */
    private function validate(): void
    {
        // 业务规则：最多成团人数不能小于最少成团人数
        if ($this->maxPeople < $this->minPeople) {
            throw new \DomainException('最多成团人数不能小于最少成团人数');
        }
    }
}
