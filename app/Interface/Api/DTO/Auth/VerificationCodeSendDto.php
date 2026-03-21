<?php

declare(strict_types=1);

namespace App\Interface\Api\DTO\Auth;

use App\Domain\Member\Contract\VerificationCodeSendInput;

final class VerificationCodeSendDto implements VerificationCodeSendInput
{
    public string $phone = '';

    public string $scene = '';

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getScene(): string
    {
        return $this->scene;
    }
}
