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

namespace App\Domain\Organization\Contract\Position;

/**
 * 岗位输入契约接口.
 */
interface PositionInput
{
    public function getId(): int;

    public function getName(): string;

    public function getDeptId(): int;

    public function getCreatedBy(): int;

    public function getUpdatedBy(): int;
}
