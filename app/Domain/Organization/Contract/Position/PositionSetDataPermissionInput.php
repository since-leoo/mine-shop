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

use App\Domain\Permission\Enum\DataPermission\PolicyType;

/**
 * 输入契约：为岗位设置数据权限策略。
 */
interface PositionSetDataPermissionInput
{
    public function getPositionId(): int;

    public function getOperatorId(): int;

    public function getPolicyType(): PolicyType;

    public function getValue(): array;
}
