<?php

declare(strict_types=1);

namespace App\Domain\Member\Contract;

interface H5SmsLoginInput
{
    public function getPhone(): string;
    public function getCode(): string;
    public function getIp(): ?string;
}
