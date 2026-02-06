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

    /**
     * @param array<int|string> $ids
     */
    public function delete(array $ids): int
    {
        return $this->repository->deleteByIds($ids);
    }

    public function setDataPermission(PositionSetDataPermissionInput $input): bool
    {
        $entity = $this->repository->findById($input->getPositionId());
        if ($entity === null) {
            return false;
        }

        $payload = [
            'policy_type' => $input->getPolicyType(),
            'value' => $input->getValue(),
        ];

        $policy = $entity->policy()->first();
        if ($policy) {
            $policy->update($payload);
        } else {
            $entity->policy()->create($payload);
        }

        return true;
    }
}
