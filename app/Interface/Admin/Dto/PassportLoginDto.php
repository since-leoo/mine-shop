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

namespace App\Interface\Admin\Dto;

use App\Domain\Auth\Enum\Type;

final class PassportLoginDto
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
}
