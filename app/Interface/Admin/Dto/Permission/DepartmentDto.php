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

namespace App\Interface\Admin\Dto\Permission;

use App\Domain\Organization\Contract\Department\DepartmentCreateInput;
use App\Domain\Organization\Contract\Department\DepartmentUpdateInput;
use Hyperf\DTO\Annotation\Validation\Required;

/**
 * 部门操作 DTO（创建和更新共用）.
 */
class DepartmentDto implements DepartmentCreateInput, DepartmentUpdateInput
{
    public ?int $id = null;

    #[Required]
    public string $name = '';

    public ?int $parent_id = null;

    public array $department_users = [];

    public array $leader = [];

    #[Required]
    public int $operator_id = 0;

    public function getId(): int
    {
        return $this->id ?? 0;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParentId(): ?int
    {
        return $this->parent_id;
    }

    public function getDepartmentUsers(): array
    {
        return $this->department_users;
    }

    public function getLeaders(): array
    {
        return $this->leader;
    }

    public function getOperatorId(): int
    {
        return $this->operator_id;
    }

    /**
     * 转换为数组（用于简单 CRUD 操作）.
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'parent_id' => $this->parent_id,
        ];

        // 创建时添加 created_by
        if ($this->id === null) {
            $data['created_by'] = $this->operator_id;
        } else {
            // 更新时添加 updated_by
            $data['updated_by'] = $this->operator_id;
        }

        return $data;
    }
}
