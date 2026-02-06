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

namespace App\Interface\Admin\DTO;

use App\Domain\Auth\Contract\LoginInput;
use App\Domain\Auth\Enum\Type;
use App\Domain\Auth\ValueObject\ClientInfo;

final class PassportLoginDto implements LoginInput
{
    public string $username = '';

    public string $password = '';

    public Type $userType = Type::SYSTEM;

    public string $ip = '0.0.0.0';

    public string $browser = 'unknown';

    public string $os = 'unknown';

    public function withClient(string $ip, string $browser, string $os): self
    {
        $this->ip = $ip;
        $this->browser = trim($browser) === '' ? 'unknown' : $browser;
        $this->os = trim($os) === '' ? 'unknown' : $os;
        return $this;
    }

    public function getClient(): array
    {
        return [
            'ip' => $this->ip,
            'browser' => $this->browser,
            'os' => $this->os,
        ];
    }

    // -- LoginInput contract implementation --
    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getUserType(): Type
    {
        return $this->userType;
    }

    public function getClientInfo(): ClientInfo
    {
        return (new ClientInfo())
            ->setIp($this->ip)
            ->setBrowser($this->browser)
            ->setOs($this->os);
    }
}
