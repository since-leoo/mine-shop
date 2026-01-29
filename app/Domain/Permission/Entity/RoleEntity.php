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
        $this->sort = $sort;
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

    private function markDirty(string $field): void
    {
        $this->dirty[$field] = true;
    }
}
