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

namespace App\Interface\Admin\DTO\Permission;

use App\Domain\Auth\Enum\Status;
use App\Domain\Auth\Enum\Type;
use App\Domain\Permission\Contract\User\UserInput;

final class UserDto implements UserInput
{
    public int $id = 0;

    public string $username = '';

    public string $password = '';

    public string $nickname = '';

    public Type $userType = Type::SYSTEM;

    public ?string $phone = null;

    public ?string $email = null;

    public ?string $avatar = null;

    public ?string $signed = null;

    public ?string $remark = null;

    public array $department = [];

    public array $position = [];

    public Status $status = Status::Normal;

    public array $backend_setting = [];

    public int $created_by = 0;

    public int $updated_by = 0;

    public ?array $policy = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getNickname(): string
    {
        return $this->nickname;
    }

    public function getUserType(): Type
    {
        return $this->userType;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function getSigned(): ?string
    {
        return $this->signed;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function getDepartmentIds(): array
    {
        return $this->department;
    }

    public function getPositionIds(): array
    {
        return $this->position;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getBackendSetting(): array
    {
        return $this->backend_setting;
    }

    public function getCreatedBy(): int
    {
        return $this->created_by;
    }

    public function getUpdatedBy(): int
    {
        return $this->updated_by;
    }

    public function getPolicy(): ?array
    {
        return $this->policy;
    }
}
