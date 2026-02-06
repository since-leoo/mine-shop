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

use App\Domain\Permission\Contract\User\UserResetPasswordInput;

final class UserResetPasswordDto implements UserResetPasswordInput
{
    public int $user_id = 0;

    public int $operator_id = 0;

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getOperatorId(): int
    {
        return $this->operator_id;
    }
}
