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

namespace App\Domain\Infrastructure\AuditLog\Entity;

final class UserLoginLogEntry
{
    public function __construct(
        private int $id,
        private string $username,
        private string $ip,
        private string $os,
        private string $browser,
        private int $status,
        private string $message,
        private string $loginTime,
        private string $remark
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getOs(): string
    {
        return $this->os;
    }

    public function getBrowser(): string
    {
        return $this->browser;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getLoginTime(): string
    {
        return $this->loginTime;
    }

    public function getRemark(): string
    {
        return $this->remark;
    }
}
