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

use App\Domain\Organization\Service\DomainDepartmentService;

final class AppDepartmentQueryService
{
    public function __construct(private readonly DomainDepartmentService $departmentService) {}

    public function getList(array $filters): array
    {
        return $this->departmentService->getList($filters)->toArray();
    }

    public function positions(int $id): array
    {
        $entity = $this->departmentService->findById($id);
        if (! $entity) {
            return [];
        }
        return $entity->positions()->get(['id', 'name'])->toArray();
    }
}
