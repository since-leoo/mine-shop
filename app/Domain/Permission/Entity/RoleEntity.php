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
use App\Domain\Permission\ValueObject\GrantPermissionsVo;

/**
 * 角色实体.
 */
final class RoleEntity
{
    private int $id = 0;

    private string $name = '';

    private string $code = '';

    private Status $status = Status::Normal;

    private int $sort = 0;

    private ?string $remark = null;

    private int $createdBy = 0;

    private int $updatedBy = 0;

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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name = ''): self
    {
        return $this->rename($name);
    }

    public function rename(string $name): self
    {
        $name = trim($name);
        if ($name === '') {
            throw new \DomainException('角色名称不能为空');
        }
        $this->name = $name;
        $this->markDirty('name');
        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code = ''): self
    {
        return $this->assignCode($code);
    }

    public function assignCode(string $code): self
    {
        $code = trim($code);
        if ($code === '') {
            throw new \DomainException('角色编码不能为空');
        }
        $this->code = $code;
        $this->markDirty('code');
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

    public function getSort(): int
    {
        return $this->sort;
    }

    public function setSort(int $sort = 0): self
    {
        return $this->applySort($sort);
    }

    public function applySort(int $sort): self
    {
        $this->sort = max(0, $sort);
        $this->markDirty('sort');
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
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'code' => $this->code,
            'status' => $this->status,
            'sort' => $this->sort,
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

    /**
     * 授予权限（实体行为方法）.
     *
     * @param array<int> $menuIds 菜单ID列表
     * @param bool $isSuperAdmin 是否为超级管理员角色
     * @throws \DomainException
     */
    public function grantPermissions(array $menuIds, bool $isSuperAdmin = false): GrantPermissionsVo
    {
        // 1. 前置条件检查
        if ($this->status !== Status::Normal) {
            throw new \DomainException(
                "角色状态为 {$this->status->name}，无法授予权限"
            );
        }

        // 2. 业务规则：超级管理员角色不能修改权限
        if ($isSuperAdmin) {
            throw new \DomainException(
                '超级管理员角色的权限不能被修改'
            );
        }

        // 3. 空数组表示清空所有权限
        if (empty($menuIds)) {
            return new GrantPermissionsVo(
                success: true,
                message: '已清空所有权限',
                menuIds: [],
                shouldDetach: true
            );
        }

        // 4. 返回需要同步的菜单ID
        return new GrantPermissionsVo(
            success: true,
            message: '权限授予成功',
            menuIds: $menuIds,
            shouldDetach: false
        );
    }

    /**
     * 检查是否为超级管理员角色.
     */
    public function isSuperAdmin(): bool
    {
        return $this->code === 'SuperAdmin';
    }

    /**
     * 检查是否可以授予权限.
     */
    public function canGrantPermission(): bool
    {
        return $this->status === Status::Normal && ! $this->isSuperAdmin();
    }

    private function markDirty(string $field): void
    {
        $this->dirty[$field] = true;
    }
}
