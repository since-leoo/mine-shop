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
use App\Domain\Permission\ValueObject\ButtonPermission;

/**
 * 菜单实体.
 */
final class MenuEntity
{
    private int $id = 0;

    private int $parentId = 0;

    private string $name = '';

    private ?string $path = null;

    private ?string $component = null;

    private ?string $redirect = null;

    private Status $status = Status::Normal;

    private int $sort = 0;

    private ?string $remark = null;

    /**
     * @var array<string, mixed>
     */
    private array $meta = [];

    private int $createdBy = 0;

    private int $updatedBy = 0;

    /**
     * @var ButtonPermission[]
     */
    private array $buttonPermissions = [];

    private bool $buttonDirty = false;

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

    public function getParentId(): int
    {
        return $this->parentId;
    }

    public function setParentId(int $parentId = 0): self
    {
        return $this->changeParent($parentId);
    }

    public function changeParent(int $parentId): self
    {
        if ($parentId < 0) {
            throw new \DomainException('父级菜单无效');
        }
        $this->parentId = $parentId;
        $this->markDirty('parent_id');
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
            throw new \DomainException('菜单名称不能为空');
        }
        $this->name = $name;
        $this->markDirty('name');
        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path = null): self
    {
        $this->path = $path ?: null;
        $this->markDirty('path');
        return $this;
    }

    public function getComponent(): ?string
    {
        return $this->component;
    }

    public function setComponent(?string $component = null): self
    {
        $this->component = $component ?: null;
        $this->markDirty('component');
        return $this;
    }

    public function getRedirect(): ?string
    {
        return $this->redirect;
    }

    public function setRedirect(?string $redirect = null): self
    {
        $this->redirect = $redirect ?: null;
        $this->markDirty('redirect');
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

    /**
     * @return array<string, mixed>
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /**
     * @param array<string, mixed> $meta
     */
    public function setMeta(array $meta = []): self
    {
        $meta['type'] = $this->normalizeMenuType($meta['type'] ?? null);
        $this->meta = $meta;
        $this->markDirty('meta');
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
     * @return ButtonPermission[]
     */
    public function getButtonPermissions(): array
    {
        return $this->buttonPermissions;
    }

    /**
     * @param array<int, array<string, mixed>|ButtonPermission> $permissions
     */
    public function setButtonPermissions(array $permissions = []): self
    {
        $this->buttonDirty = true;
        $this->buttonPermissions = array_map(static function ($permission) {
            if ($permission instanceof ButtonPermission) {
                return $permission;
            }
            return ButtonPermission::fromArray($permission);
        }, $permissions);
        return $this;
    }

    public function shouldSyncButtons(): bool
    {
        return $this->buttonDirty && $this->allowsButtonPermissions();
    }

    public function allowsButtonPermissions(): bool
    {
        return $this->metaType() === 'M';
    }

    public function metaType(): string
    {
        return $this->meta['type'] ?? 'M';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buttonPayloads(): array
    {
        return array_map(
            static fn (ButtonPermission $permission) => [
                'id' => $permission->id(),
                'code' => $permission->code(),
                'title' => $permission->title(),
                'i18n' => $permission->i18n(),
            ],
            $this->buttonPermissions
        );
    }

    public function ensureCanPersist(bool $isCreate = false): void
    {
        if ($isCreate && $this->id !== 0) {
            throw new \DomainException('新增菜单不应提前设置ID');
        }

        if ($isCreate || isset($this->dirty['name'])) {
            if (trim($this->name) === '') {
                throw new \DomainException('菜单名称不能为空');
            }
        }

        if (! in_array($this->metaType(), ['M', 'C', 'I', 'L', 'B'], true)) {
            throw new \DomainException('菜单类型不合法');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'parent_id' => $this->parentId,
            'name' => $this->name,
            'path' => $this->path,
            'component' => $this->component,
            'redirect' => $this->redirect,
            'status' => $this->status->value,
            'sort' => $this->sort,
            'remark' => $this->remark,
            'meta' => $this->meta,
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

    private function markDirty(string $field): void
    {
        $this->dirty[$field] = true;
    }

    private function normalizeMenuType(?string $type): string
    {
        $type = strtoupper(trim((string) ($type ?? '')));
        $allowed = ['M', 'C', 'I', 'L', 'B'];
        if (! in_array($type, $allowed, true)) {
            return 'M';
        }
        return $type;
    }
}
