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

namespace App\Domain\Permission\Contract\User;

/**
 * 输入契约：为用户授予角色.
 */
interface UserGrantRolesInput
{
    public function getUserId(): int;

    public function getRoleCodes(): array;

    public function getOperatorId(): int;
}
