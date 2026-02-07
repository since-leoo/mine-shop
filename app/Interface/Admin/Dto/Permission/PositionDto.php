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

use App\Domain\Organization\Contract\Position\PositionInput;

final class PositionDto implements PositionInput
{
    public int $id = 0;

    public string $name = '';

    public int $dept_id = 0;

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

    public function getDeptId(): int
    {
        return $this->dept_id;
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
