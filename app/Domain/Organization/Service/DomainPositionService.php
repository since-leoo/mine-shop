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

namespace App\Domain\Organization\Service;

use App\Domain\Organization\Contract\Position\PositionInput;
use App\Domain\Organization\Repository\PositionRepository;
use App\Infrastructure\Abstract\IService;

/**
 * 职位领域服务.
 */
final class DomainPositionService extends IService
{
    public function __construct(
        private readonly PositionRepository $repository
    ) {}

    /**
     * 创建职位.
     */
    public function create(PositionInput $input): mixed
    {
        $payload = [
            'name' => $input->getName(),
            'dept_id' => $input->getDeptId(),
            'created_by' => $input->getCreatedBy(),
        ];
        return $this->repository->create($payload);
    }

    /**
     * 更新职位.
     */
    public function update(int $id, PositionInput $input): bool
    {
        $payload = [
            'name' => $input->getName(),
            'dept_id' => $input->getDeptId(),
            'updated_by' => $input->getUpdatedBy(),
        ];
        return $this->repository->updateById($id, $payload);
    }

    /**
     * 删除职位.
     *
     * @param array<int> $ids
     */
    public function delete(array $ids): int
    {
        return $this->repository->deleteByIds($ids);
    }

    /**
     * 设置数据权限.
     *
     * @param array<string, mixed> $payload
     */
    public function setDataPermission(int $id, array $payload): bool
    {
        return $this->repository->updateById($id, $payload);
    }

    /**
     * 设置岗位数据权限策略.
     */
    public function setDataPermissionPolicy(int $positionId, string $policyType, mixed $value): bool
    {
        $positionModel = $this->repository->findById($positionId);
        if ($positionModel === null) {
            return false;
        }

        $result = $positionModel->setDataPermissionPolicy($policyType, $value);

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
