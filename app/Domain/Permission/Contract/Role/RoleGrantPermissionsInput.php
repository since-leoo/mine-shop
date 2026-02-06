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

namespace App\Domain\Permission\Contract\Role;

/**
 * 输入契约：为角色授予权限（菜单/按钮权限码）。
 */
interface RoleGrantPermissionsInput
{
    public function getRoleId(): int;

    public function getPermissionCodes(): array;

    public function getOperatorId(): int;
}
