<?php

declare(strict_types=1);

namespace App\Interface\Api\DTO\Auth;

use App\Domain\Member\Contract\H5SmsLoginInput;

final class H5SmsLoginDto implements H5SmsLoginInput
{
    public string $phone = '';
    public string $code = '';
    public ?string $ip = null;

    public function getPhone(): string { return $this->phone; }
    public function getCode(): string { return $this->code; }
    public function getIp(): ?string { return $this->ip; }
}
