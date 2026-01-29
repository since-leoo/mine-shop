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

namespace App\Domain\Auth\ValueObject;

/**
 * 登录客户端信息.
 */
final class ClientInfo
{
    private string $ip = '0.0.0.0';

    private string $os = 'unknown';

    private string $browser = 'unknown';

    public function getIp(): string
    {
        return $this->ip;
    }

    public function setIp(string $ip = '0.0.0.0'): self
    {
        $this->ip = $ip;
        return $this;
    }

    public function getOs(): string
    {
        return $this->os;
    }

    public function setOs(string $os = 'unknown'): self
    {
        $this->os = $os;
        return $this;
    }

    public function getBrowser(): string
    {
        return $this->browser;
    }

    public function setBrowser(string $browser = 'unknown'): self
    {
        $this->browser = $browser;
        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'ip' => $this->ip,
            'os' => $this->os,
            'browser' => $this->browser,
        ];
    }
}
