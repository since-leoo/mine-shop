<?php

declare(strict_types=1);

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
