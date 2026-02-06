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

use App\Domain\Permission\Contract\Role\RoleGrantPermissionsInput;
use App\Domain\Permission\Entity\RoleEntity;
use App\Domain\Permission\Repository\MenuRepository;
use App\Domain\Permission\Repository\RoleRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Permission\Role;

final class RoleService extends IService
{
    public function __construct(
        private readonly RoleRepository $roleRepository,
        private readonly MenuRepository $menuRepository
    ) {}

    public function create(RoleEntity $entity): Role
    {
        return $this->roleRepository->create($entity->toArray());
    }

    public function update(int $id, RoleEntity $entity): bool
    {
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

    public function grantPermissions(RoleGrantPermissionsInput $input): void
    {
        /** @var null|Role $role */
        $role = $this->roleRepository->findById($input->getRoleId());
        if (! $role) {
            return;
        }

        $permissionCodes = $input->getPermissionCodes();
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
