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

namespace App\Interface\Admin\Dto\Permission;

use App\Domain\Permission\Contract\Role\RoleGrantPermissionsInput;

final class RoleGrantPermissionsDto implements RoleGrantPermissionsInput
{
    public int $role_id = 0;

    public array $permissions = [];

    public int $operator_id = 0;

    public function getRoleId(): int
    {
        return $this->role_id;
    }

    public function getPermissionCodes(): array
    {
        return $this->permissions;
    }

    public function getOperatorId(): int
    {
        return $this->operator_id;
    }
}
