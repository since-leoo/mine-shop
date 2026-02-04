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

namespace App\Domain\Permission\Entity;

use App\Domain\Auth\Enum\Status;
use App\Domain\Auth\Enum\Type;
use App\Domain\Permission\ValueObject\DataPolicy;

/**
 * 用户实体.
 */
final class UserEntity
{
    private int $id = 0;

    private string $username = '';

    private ?string $password = null;

    private Type $userType = Type::SYSTEM;

    private string $nickname = '';

    private ?string $phone = null;

    private ?string $email = null;

    private ?string $avatar = null;

    private ?string $signed = null;

    private Status $status = Status::Normal;

    /**
     * @var array<string, mixed>
     */
    private array $backendSetting = [];

    private ?string $remark = null;

    private int $createdBy = 0;

    private int $updatedBy = 0;

    /**
     * @var int[]
     */
    private array $departmentIds = [];

    private bool $departmentDirty = false;

    /**
     * @var int[]
     */
    private array $positionIds = [];

    private bool $positionDirty = false;

    private ?DataPolicy $policy = null;

    private bool $policyDirty = false;

    /**
     * @var array<string, bool>
     */
    private array $dirty = [];

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id = 0): self
    {
        $this->id = $id;
        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username = ''): self
    {
        $this->username = $username;
        $this->markDirty('username');
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password = null): self
    {
        $this->password = $password;
        $this->markDirty('password');
        return $this;
    }

    public function getUserType(): Type
    {
        return $this->userType;
    }

    public function setUserType(Type $userType = Type::SYSTEM): self
    {
        $this->userType = $userType;
        $this->markDirty('user_type');
        return $this;
    }

    public function getNickname(): string
    {
        return $this->nickname;
    }

    public function setNickname(string $nickname = ''): self
    {
        $this->nickname = $nickname;
        $this->markDirty('nickname');
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone = null): self
    {
        $this->phone = $phone;
        $this->markDirty('phone');
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email = null): self
    {
        $this->email = $email;
        $this->markDirty('email');
        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar = null): self
    {
        $this->avatar = $avatar;
        $this->markDirty('avatar');
        return $this;
    }

    public function getSigned(): ?string
    {
        return $this->signed;
    }

    public function setSigned(?string $signed = null): self
    {
        $this->signed = $signed;
        $this->markDirty('signed');
        return $this;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function setStatus(Status $status = Status::Normal): self
    {
        return $this->changeStatus($status);
    }

    public function changeStatus(Status $status): self
    {
        $this->status = $status;
        $this->markDirty('status');
        return $this;
    }

    public function activate(): self
    {
        return $this->changeStatus(Status::Normal);
    }

    public function disable(): self
    {
        return $this->changeStatus(Status::DISABLE);
    }

    /**
     * @return array<string, mixed>
     */
    public function getBackendSetting(): array
    {
        return $this->backendSetting;
    }

    /**
     * @param array<string, mixed> $backendSetting
     */
    public function setBackendSetting(array $backendSetting = []): self
    {
        $this->backendSetting = $backendSetting;
        $this->markDirty('backend_setting');
        return $this;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark = null): self
    {
        $this->remark = $remark;
        $this->markDirty('remark');
        return $this;
    }

    public function getCreatedBy(): int
    {
        return $this->createdBy;
    }

    public function setCreatedBy(int $createdBy = 0): self
    {
        $this->createdBy = $createdBy;
        $this->markDirty('created_by');
        return $this;
    }

    public function getUpdatedBy(): int
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(int $updatedBy = 0): self
    {
        $this->updatedBy = $updatedBy;
        $this->markDirty('updated_by');
        return $this;
    }

    /**
     * @return int[]
     */
    public function getDepartmentIds(): array
    {
        return $this->departmentIds;
    }

    /**
     * @param int[] $departmentIds
     */
    public function setDepartmentIds(array $departmentIds = []): self
    {
        $this->departmentIds = $departmentIds;
        $this->departmentDirty = true;
        return $this;
    }

    public function shouldSyncDepartments(): bool
    {
        return $this->departmentDirty;
    }

    /**
     * @return int[]
     */
    public function getPositionIds(): array
    {
        return $this->positionIds;
    }

    /**
     * @param int[] $positionIds
     */
    public function setPositionIds(array $positionIds = []): self
    {
        $this->positionIds = $positionIds;
        $this->positionDirty = true;
        return $this;
    }

    public function shouldSyncPositions(): bool
    {
        return $this->positionDirty;
    }

    public function getPolicy(): ?DataPolicy
    {
        return $this->policy;
    }

    public function setPolicy(?DataPolicy $policy): self
    {
        $this->policy = $policy;
        $this->policyDirty = true;
        return $this;
    }

    public function shouldSyncPolicy(): bool
    {
        return $this->policyDirty;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'username' => $this->username,
            'password' => $this->password,
            'user_type' => $this->userType,
            'nickname' => $this->nickname,
            'phone' => $this->phone,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'signed' => $this->signed,
            'status' => $this->status,
            'backend_setting' => $this->backendSetting,
            'remark' => $this->remark,
            'created_by' => $this->createdBy ?: null,
            'updated_by' => $this->updatedBy ?: null,
        ];

        if ($this->dirty === []) {
            return array_filter($data, static fn ($value) => $value !== null);
        }

        return array_filter(
            $data,
            function ($value, string $field) {
                return isset($this->dirty[$field]) && $value !== null;
            },
            \ARRAY_FILTER_USE_BOTH
        );
    }

    public function ensureCanPersist(bool $isCreate = false): void
    {
        if ($isCreate || isset($this->dirty['username'])) {
            if (trim($this->username) === '') {
                throw new \DomainException('用户名不能为空');
            }
        }
        if ($isCreate || isset($this->dirty['nickname'])) {
            if (trim($this->nickname) === '') {
                throw new \DomainException('用户昵称不能为空');
            }
        }
        if ($isCreate && ($this->password === null || $this->password === '')) {
            throw new \DomainException('新增用户必须设置密码');
        }
    }

    private function markDirty(string $field): void
    {
        $this->dirty[$field] = true;
    }
}
