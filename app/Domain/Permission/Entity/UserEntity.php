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
use App\Domain\Permission\Contract\User\UserInput;
use App\Domain\Permission\Enum\DataPermission\PolicyType;
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

    public function verifyPassword(string $raw): bool
    {
        return $this->password !== null && password_verify($raw, $this->password);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'username' => $this->getUsername(),
            'password' => $this->getPassword(),
            'user_type' => $this->getUserType(),
            'nickname' => $this->getNickname(),
            'phone' => $this->getPhone(),
            'email' => $this->getPhone(),
            'avatar' => $this->getAvatar(),
            'signed' => $this->getSigned(),
            'status' => $this->getStatus(),
            'backend_setting' => $this->getBackendSetting(),
            'remark' => $this->getRemark(),
            'created_by' => $this->getCreatedBy(),
            'updated_by' => $this->getUpdatedBy(),
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

    /**
     * 创建.
     */
    public function create(UserInput $input): self
    {
        $input->getUsername() && $this->setUsername($input->getUsername());
        $input->getPassword() && $this->setPassword($input->getPassword());
        $input->getNickname() && $this->setNickname($input->getNickname());
        $input->getPhone() && $this->setPhone($input->getPhone());
        $input->getEmail() && $this->setEmail($input->getEmail());
        $input->getAvatar() && $this->setAvatar($input->getAvatar());
        $input->getSigned() && $this->setSigned($input->getSigned());
        $input->getRemark() && $this->setRemark($input->getRemark());
        $input->getUserType() && $this->setUserType($input->getUserType());
        $input->getStatus() && $this->setStatus($input->getStatus());
        $input->getBackendSetting() && $this->setBackendSetting($input->getBackendSetting());
        $input->getCreatedBy() && $this->setCreatedBy($input->getCreatedBy());
        $input->getUpdatedBy() && $this->setUpdatedBy($input->getUpdatedBy());
        $input->getDepartmentIds() && $this->setDepartmentIds(array_map('intval', $input->getDepartmentIds()));
        $input->getPositionIds() && $this->setPositionIds(array_map('intval', $input->getPositionIds()));

        // 数据权限策略
        if ($policy = $input->getPolicy()) {
            $vo = new DataPolicy();
            ! empty($policy['id']) && $vo->setId((int) $policy['id']);
            ! empty($policy['policy_type']) && $vo->setType(PolicyType::from((string) $policy['policy_type']));
            ! empty($policy['value']) && $vo->setValue((array) $policy['value']);
            $this->setPolicy($vo);
        }
        return $this;
    }

    /**
     * 更新.
     */
    public function update(UserInput $input): self
    {
        $input->getUsername() && $this->setUsername($input->getUsername());
        $input->getPassword() && $this->setPassword($input->getPassword());
        $input->getNickname() && $this->setNickname($input->getNickname());
        $input->getPhone() && $this->setPhone($input->getPhone());
        $input->getEmail() && $this->setEmail($input->getEmail());
        $input->getAvatar() && $this->setAvatar($input->getAvatar());
        $input->getSigned() && $this->setSigned($input->getSigned());
        $input->getRemark() && $this->setRemark($input->getRemark());
        $input->getUserType() && $this->setUserType($input->getUserType());
        $input->getStatus() && $this->setStatus($input->getStatus());
        $input->getBackendSetting() && $this->setBackendSetting($input->getBackendSetting());
        $input->getUpdatedBy() && $this->setUpdatedBy($input->getUpdatedBy());
        $input->getDepartmentIds() && $this->setDepartmentIds(array_map('intval', $input->getDepartmentIds()));
        $input->getPositionIds() && $this->setPositionIds(array_map('intval', $input->getPositionIds()));

        // 数据权限策略
        if ($policy = $input->getPolicy()) {
            $vo = new DataPolicy();
            ! empty($policy['id']) && $vo->setId((int) $policy['id']);
            ! empty($policy['policy_type']) && $vo->setType(PolicyType::from((string) $policy['policy_type']));
            ! empty($policy['value']) && $vo->setValue((array) $policy['value']);
            $this->setPolicy($vo);
        }
        return $this;
    }

    private function markDirty(string $field): void
    {
        $this->dirty[$field] = true;
    }
}
