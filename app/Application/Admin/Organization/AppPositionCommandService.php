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

namespace App\Application\Admin\Organization;

use App\Domain\Organization\Contract\Position\PositionInput;
use App\Domain\Organization\Contract\Position\PositionSetDataPermissionInput;
use App\Domain\Organization\Service\DomainPositionService;
use App\Domain\Permission\Contract\Common\DeleteInput;
use App\Infrastructure\Model\Permission\Position;

final class AppPositionCommandService
{
    public function __construct(private readonly DomainPositionService $positionService) {}

    public function create(PositionInput $input): Position
    {
        return $this->positionService->create($input);
    }

    public function update(int $id, PositionInput $input): bool
    {
        return $this->positionService->update($id, $input);
    }

    public function delete(DeleteInput $input): int
    {
        return $this->positionService->delete($input->getIds());
    }

    public function setDataPermission(PositionSetDataPermissionInput $input): bool
    {
        // 步骤1: 从 DTO 获取岗位ID
        $positionId = $input->getPositionId();

        // 步骤2: 通过 Repository 获取 Model
        $positionModel = $this->positionService->repository->findById($positionId);
        if ($positionModel === null) {
            return false;
        }

        // 步骤3: 调用 Model 的行为方法（包含业务规则验证）
        $result = $positionModel->setDataPermissionPolicy(
            $input->getPolicyType(),
            $input->getValue()
        );

        // 步骤4: 根据结果执行持久化操作
        if ($result->success) {
            $payload = [
                'policy_type' => $result->policyType,
                'value' => $result->value,
            ];

            $policy = $positionModel->policy()->first();
            if ($policy) {
                $policy->update($payload);
            } else {
                $positionModel->policy()->create($payload);
            }
        }

        return $result->success;
    }
}
