<?php

declare(strict_types=1);

namespace App\Domain\Permission\Contract\User;

/**
 * 输入契约：重置用户密码.
 */
interface UserResetPasswordInput
{
    public function getUserId(): int;

    public function getOperatorId(): int;
}
