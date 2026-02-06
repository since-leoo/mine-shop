<?php

declare(strict_types=1);

namespace App\Interface\Admin\DTO\Permission;

use App\Domain\Permission\Contract\Position\PositionSetDataPermissionInput;
use App\Domain\Permission\Enum\DataPermission\PolicyType;

final class PositionSetDataPermissionDto implements PositionSetDataPermissionInput
{
    public int $position_id = 0;
    public int $operator_id = 0;
    public PolicyType $policy_type = PolicyType::Self;
    public array $value = [];

    public function getPositionId(): int { return $this->position_id; }
    public function getOperatorId(): int { return $this->operator_id; }
    public function getPolicyType(): PolicyType { return $this->policy_type; }
    public function getValue(): array { return $this->value; }
}
