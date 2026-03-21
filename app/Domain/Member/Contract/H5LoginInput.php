<?php

declare(strict_types=1);

namespace App\Domain\Member\Contract;

interface H5LoginInput
{
    public function getPhone(): string;

    public function getPassword(): string;

    public function getIp(): ?string;
}
