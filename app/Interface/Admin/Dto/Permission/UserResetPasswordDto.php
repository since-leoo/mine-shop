<?php

declare(strict_types=1);

namespace App\Interface\Admin\DTO\Permission;

use App\Domain\Permission\Contract\User\UserResetPasswordInput;

final class UserResetPasswordDto implements UserResetPasswordInput
{
    public int $user_id = 0;
    public int $operator_id = 0;

    public function getUserId(): int { return $this->user_id; }
    public function getOperatorId(): int { return $this->operator_id; }
}
