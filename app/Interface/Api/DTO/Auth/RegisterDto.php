<?php

declare(strict_types=1);

namespace App\Interface\Api\DTO\Auth;

use App\Domain\Member\Contract\RegisterInput;

final class RegisterDto implements RegisterInput
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
