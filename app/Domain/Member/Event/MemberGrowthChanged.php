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

namespace App\Domain\Member\Event;

/**
 * 成长值变动事件.
 */
final class MemberGrowthChanged
{
    public function __construct(
        public readonly int $memberId,
        public readonly int $beforeValue,
        public readonly int $afterValue,
        public readonly int $changeAmount,
        public readonly string $source,
        public readonly string $remark = '',
    ) {}
}
