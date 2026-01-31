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

namespace App\Application\Permission\Service;

use App\Domain\Permission\Repository\PositionRepository;
use App\Infrastructure\Model\Permission\Position;

final class PositionCommandService
{
    public function __construct(private readonly PositionRepository $repository) {}

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

    public function setDataPermission(int $id, array $policy): bool
    {
        $entity = $this->repository->findById($id);
        if ($entity === null) {
            return false;
        }

        $policyEntity = $entity->policy()->first();
        if (empty($policyEntity)) {
            $entity->policy()->create($policy);
        } else {
            $policyEntity->update($policy);
        }

        return true;
    }
}
