<?php

declare(strict_types=1);

namespace App\Domain\Member\Contract;

interface VerificationCodeSendInput
{
    public function getPhone(): string;

    public function getScene(): string;
}
