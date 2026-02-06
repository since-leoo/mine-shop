<?php

declare(strict_types=1);

namespace App\Domain\Auth\Contract;

use App\Domain\Auth\Enum\Type;
use App\Domain\Auth\ValueObject\ClientInfo;

interface LoginInput
{
    public function getUsername(): string;
    public function getPassword(): string;
    public function getUserType(): Type;
    public function getClientInfo(): ClientInfo;
}
