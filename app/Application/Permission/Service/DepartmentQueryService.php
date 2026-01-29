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

use App\Domain\Permission\Repository\DepartmentRepository;

final class DepartmentQueryService
{
    public function __construct(private readonly DepartmentRepository $repository)
    {
    }

    public function list(array $filters): array
    {
        return $this->repository->list($filters)->toArray();
    }

    public function positions(int $id): array
    {
        $entity = $this->repository->findById($id);
        if (! $entity) {
            return [];
        }
        return $entity->positions()->get(['id', 'name'])->toArray();
    }
}
