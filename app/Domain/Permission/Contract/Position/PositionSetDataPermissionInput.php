<?php

declare(strict_types=1);

namespace App\Domain\Permission\Contract\Position;

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
