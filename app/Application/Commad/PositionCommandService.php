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

namespace App\Application\Commad;

use App\Domain\Permission\Contract\Common\DeleteInput;
use App\Domain\Permission\Contract\Position\PositionSetDataPermissionInput;
use App\Domain\Permission\Repository\PositionRepository;
use App\Infrastructure\Model\Permission\Position;

final class PositionCommandService
{
    public function __construct(public readonly PositionRepository $repository) {}

    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): Position
    {
        return $this->repository->create($payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(int $id, array $payload): bool
    {
        return $this->repository->updateById($id, $payload);
    }

    public function delete(DeleteInput $input): int
    {
        return $this->repository->deleteByIds($input->getIds());
    }

    public function setDataPermission(PositionSetDataPermissionInput $input): bool
    {
        // 步骤1: 从 DTO 获取岗位ID
        $positionId = $input->getPositionId();

        // 步骤2: 通过 Repository 获取 Model
        $positionModel = $this->repository->findById($positionId);
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
