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

use App\Domain\Permission\Entity\RoleEntity;
use App\Infrastructure\Model\Permission\Role;
use App\Domain\Permission\Repository\MenuRepository;
use App\Domain\Permission\Repository\RoleRepository;
use Hyperf\DbConnection\Db;

final class RoleCommandService
{
    public function __construct(
        private readonly RoleRepository $roleRepository,
        private readonly MenuRepository $menuRepository
    ) {}

    public function create(RoleEntity $entity): Role
    {
        return Db::transaction(function () use ($entity) {
            return $this->roleRepository->getModel()->newQuery()->create($entity->toArray());
        });
    }

    public function update(int $id, RoleEntity $entity): bool
    {
        return Db::transaction(function () use ($id, $entity) {
            $payload = $entity->toArray();
            if ($payload === []) {
                return true;
            }
            return $this->roleRepository->updateById($id, $payload);
        });
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
