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
 * 会员注册事件.
 */
final class MemberRegistered
{
    public function __construct(
        public readonly int $memberId,
        public readonly string $source,
    ) {}
}
