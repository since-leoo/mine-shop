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

namespace App\Domain\Permission\Service;

use App\Domain\Permission\Entity\RoleEntity;
use App\Domain\Permission\Repository\MenuRepository;
use App\Domain\Permission\Repository\RoleRepository;
use App\Infrastructure\Model\Permission\Role;

final class RoleService
{
    public function __construct(
        private readonly RoleRepository $roleRepository,
        private readonly MenuRepository $menuRepository
    ) {}

    public function create(RoleEntity $entity): Role
    {
        $entity->ensureCanPersist(true);
        return $this->roleRepository->create($entity->toArray());
    }

    public function update(int $id, RoleEntity $entity): bool
    {
        $entity->ensureCanPersist();
        $payload = $entity->toArray();
        if ($payload === []) {
            return true;
        }
        return $this->roleRepository->updateById($id, $payload);
    }

    /**
     * @param array<int> $ids
     */
    public function delete(array $ids): int
    {
        return $this->roleRepository->deleteByIds($ids);
    }

    /**
     * @param string[] $permissionCodes
     */
    public function grantPermissions(int $roleId, array $permissionCodes): void
    {
        /** @var null|Role $role */
        $role = $this->roleRepository->findById($roleId);
        if (! $role) {
            return;
        }

        if ($permissionCodes === []) {
            $role->menus()->detach();
            return;
        }

        $menuIds = $this->menuRepository
            ->listByCodes($permissionCodes)
            ->pluck('id')
            ->toArray();

        $role->menus()->sync($menuIds);
    }
}
