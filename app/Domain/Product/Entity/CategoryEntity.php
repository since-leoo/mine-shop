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

/**
 * 分类实体.
 */
final class CategoryEntity
{
    private int $id = 0;

    private int $parentId = 0;

    private string $name = '';

    private ?string $icon = null;

    private ?string $description = null;

    private int $sort = 0;

    private int $level = 1;

    private string $status = 'active';

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
        $this->parentId = $parentId;
        $this->level = $parentId > 0 ? $this->level : 1;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name = ''): self
    {
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

    public function getSort(): int
    {
        return $this->sort;
    }

    public function setSort(int $sort = 0): self
    {
        $this->sort = $sort;
        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level = 1): self
    {
        $this->level = $level;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status = 'active'): self
    {
        $this->status = $status;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->status === CategoryStatus::ACTIVE->value;
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
            'name' => $this->getName() ?: null,
            'icon' => $this->getIcon(),
            'description' => $this->getDescription(),
            'sort' => $this->getSort(),
            'level' => $this->getLevel(),
            'status' => $this->getStatus(),
        ], static fn ($v) => $v !== null);
    }
}
