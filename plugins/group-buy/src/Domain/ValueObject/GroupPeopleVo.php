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

namespace Plugin\Since\GroupBuy\Domain\ValueObject;

final class GroupPeopleVo
{
    public function __construct(
        private readonly int $minPeople,
        private readonly int $maxPeople
    ) {
        $this->validate();
    }

    public function getMinPeople(): int
    {
        return $this->minPeople;
    }

    public function getMaxPeople(): int
    {
        return $this->maxPeople;
    }

    public function canFormGroup(int $currentPeople): bool
    {
        return $currentPeople >= $this->minPeople && $currentPeople <= $this->maxPeople;
    }

    private function validate(): void
    {
        if ($this->maxPeople < $this->minPeople) {
            throw new \DomainException('最多成团人数不能小于最少成团人数');
        }
    }
}
