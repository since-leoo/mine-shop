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

namespace App\Application\Admin\Permission;

use App\Domain\Permission\Service\DomainRoleService;
use App\Infrastructure\Model\Permission\Role;
use Hyperf\Collection\Collection;

final class AppRoleQueryService
{
    public function __construct(private readonly DomainRoleService $roleService) {}

    /**
     * 分页查询角色.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->roleService->page($filters, $page, $pageSize);
    }

    public function list(array $filters = []): Collection
    {
        return $this->roleService->getList($filters);
    }

    public function find(int $id): ?Role
    {
        return $this->roleService->findById($id);
    }

    public function permissions(int $id): Collection
    {
        $role = $this->roleService->findById($id);
        if (! $role) {
            return new Collection();
        }
        return $role->menus()->get();
    }
}
