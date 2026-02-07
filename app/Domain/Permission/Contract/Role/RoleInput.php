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

namespace App\Domain\Permission\Contract\Role;

use App\Domain\Auth\Enum\Status;

/**
 * 角色输入契约接口（统一创建和更新）.
 */
interface RoleInput
{
    public function getId(): int;

    public function getName(): string;

    public function getCode(): string;

    public function getStatus(): Status;

    public function getSort(): int;

    public function getRemark(): ?string;

    public function getCreatedBy(): int;

    public function getUpdatedBy(): int;
}
