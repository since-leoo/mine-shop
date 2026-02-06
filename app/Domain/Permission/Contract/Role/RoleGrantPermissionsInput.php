<?php

declare(strict_types=1);

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
