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

use App\Domain\Permission\Contract\User\UserGrantRolesInput;

final class UserGrantRolesDto implements UserGrantRolesInput
{
    public int $user_id = 0;

    public array $role_codes = [];

    public int $operator_id = 0;

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getRoleCodes(): array
    {
        return $this->role_codes;
    }

    public function getOperatorId(): int
    {
        return $this->operator_id;
    }
}
