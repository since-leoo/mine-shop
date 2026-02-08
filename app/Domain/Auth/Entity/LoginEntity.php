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

namespace App\Domain\Auth\Entity;

use App\Domain\Auth\Contract\LoginInput;
use App\Domain\Auth\Enum\Type;
use App\Domain\Auth\ValueObject\ClientInfo;

/**
 * 登录实体，聚合基础认证参数.
 */
final class LoginEntity
{
    private string $username = '';

    private string $password = '';

    private Type $userType = Type::SYSTEM;

    private ClientInfo $client;

    public function __construct()
    {
        $this->client = new ClientInfo();
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username = ''): self
    {
        $this->username = $username;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password = ''): self
    {
        $this->password = $password;
        return $this;
    }

    public function getUserType(): Type
    {
        return $this->userType;
    }

    public function setUserType(Type $userType = Type::SYSTEM): self
    {
        $this->userType = $userType;
        return $this;
    }

    public function getClient(): ClientInfo
    {
        return $this->client;
    }

    public function setClient(ClientInfo $client): self
    {
        $this->client = $client;
        return $this;
    }

    public function login(LoginInput $input): self
    {
        $this->setUsername($input->getUsername())->setPassword($input->getPassword());

        return $this;
    }
}
