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

use App\Domain\Permission\Repository\DepartmentRepository;
use App\Infrastructure\Model\Permission\Department;
use Hyperf\DbConnection\Db;

final class DepartmentCommandService
{
    public function __construct(public readonly DepartmentRepository $repository) {}

    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload, callable $afterCreate): Department
    {
        return Db::transaction(function () use ($payload, $afterCreate) {
            $entity = $this->repository->create($payload);
            $afterCreate($entity, $payload);
            return $entity;
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(int $id, array $payload, callable $afterUpdate): ?Department
    {
        return Db::transaction(function () use ($id, $payload, $afterUpdate) {
            $entity = $this->repository->findById($id);
            if (! $entity) {
                return null;
            }
            $afterUpdate($entity, $payload);
            return $entity;
        });
    }

    /**
     * @param \App\Domain\Permission\Contract\Common\DeleteInput $input
     */
    public function delete(\App\Domain\Permission\Contract\Common\DeleteInput $input): int
    {
        return $this->repository->deleteByIds($input->getIds());
    }
}
