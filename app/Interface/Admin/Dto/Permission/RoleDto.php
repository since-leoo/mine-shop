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

use App\Domain\Auth\Enum\Status;
use App\Domain\Permission\Contract\Role\RoleInput;

final class RoleDto implements RoleInput
{
    public int $id = 0;

    public string $name = '';

    public string $code = '';

    public Status $status = Status::Normal;

    public int $sort = 0;

    public ?string $remark = null;

    public int $created_by = 0;

    public int $updated_by = 0;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function getCreatedBy(): int
    {
        return $this->created_by;
    }

    public function getUpdatedBy(): int
    {
        return $this->updated_by;
    }
}
