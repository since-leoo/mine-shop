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

namespace App\Domain\Permission\ValueObject;

/**
 * 重置密码值对象.
 */
final class ResetPasswordVo
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly bool $needsSave
    ) {}
}
