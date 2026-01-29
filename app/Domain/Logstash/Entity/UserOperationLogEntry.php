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

namespace App\Domain\Logstash\Entity;

final class UserOperationLogEntry
{
    public function __construct(
        private int $id,
        private string $username,
        private string $method,
        private string $router,
        private string $serviceName,
        private string $ip,
        private string $ipLocation,
        private string $createdAt,
        private string $updatedAt,
        private string $remark
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getRouter(): string
    {
        return $this->router;
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getIpLocation(): string
    {
        return $this->ipLocation;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function getRemark(): string
    {
        return $this->remark;
    }
}
