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

namespace App\Domain\Product\Entity;

use App\Domain\Product\Enum\CategoryStatus;
use DomainException;

/**
 * 分类实体.
 */
final class CategoryEntity
{
    private int $id = 0;

    private ?int $parentId = null;

    private ?string $name = null;

    private ?string $icon = null;

    private ?string $description = null;

    private ?int $sort = null;

    private ?int $level = null;

    private string $status = CategoryStatus::ACTIVE->value;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id = 0): self
    {
        $this->id = $id;
        return $this;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function setParentId(int $parentId = 0): self
    {
        return $this->moveToParent($parentId);
    }

    public function moveToParent(int $parentId, ?int $level = null): self
    {
        if ($parentId < 0) {
            throw new DomainException('父级分类无效');
        }
        $this->parentId = $parentId;
        if ($level !== null) {
            $this->setLevel($level);
        }
        return $this;
    }

    public function getName(): ?string
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
            throw new DomainException('分类名称不能为空');
        }
        $this->name = $name;
        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon = null): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description = null): self
    {
        $this->description = $description;
        return $this;
    }

    public function getSort(): ?int
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
        return $this;
    }

    public function needsSort(): bool
    {
        return $this->sort === null || $this->sort <= 0;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(int $level = 1): self
    {
        return $this->assignLevel($level);
    }

    public function assignLevel(int $level): self
    {
        if ($level <= 0) {
            throw new DomainException('分类层级必须大于0');
        }
        $this->level = $level;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status = 'active'): self
    {
        return $this->changeStatus($status);
    }

    public function changeStatus(string $status): self
    {
        $values = array_map(static fn (CategoryStatus $case) => $case->value, CategoryStatus::cases());
        if (! in_array($status, $values, true)) {
            throw new DomainException('分类状态值无效');
        }
        $this->status = $status;
        return $this;
    }

    public function activate(): self
    {
        return $this->changeStatus(CategoryStatus::ACTIVE->value);
    }

    public function deactivate(): self
    {
        return $this->changeStatus(CategoryStatus::INACTIVE->value);
    }

    public function isActive(): bool
    {
        return $this->status === CategoryStatus::ACTIVE->value;
    }

    public function ensureCanPersist(bool $isCreate = false): void
    {
        if (($isCreate || $this->name !== null) && ($this->name === null || trim($this->name) === '')) {
            throw new DomainException('分类名称不能为空');
        }
    }

    public function isRoot(): bool
    {
        return (int) $this->parentId === 0;
    }

    /**
     * 转换为数组（用于持久化）.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'parent_id' => $this->getParentId(),
            'name' => $this->getName(),
            'icon' => $this->getIcon(),
            'description' => $this->getDescription(),
            'sort' => $this->getSort(),
            'level' => $this->getLevel(),
            'status' => $this->getStatus(),
        ], static fn ($v) => $v !== null);
    }
}
