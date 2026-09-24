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

namespace App\Interface\Api\DTO\Auth;

use App\Domain\Member\Contract\ForgotPasswordInput;

final class ForgotPasswordDto implements ForgotPasswordInput
{
    public string $phone = '';

    public string $password = '';

    public string $code = '';

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
