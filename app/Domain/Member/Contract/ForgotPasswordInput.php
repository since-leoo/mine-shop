<?php

declare(strict_types=1);

namespace App\Domain\Member\Contract;

interface ForgotPasswordInput
{
    public function getPhone(): string;

    public function getPassword(): string;

    public function getCode(): string;
}
