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
use App\Domain\Permission\Mapper\RoleMapper;
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
        // 步骤1: 从 DTO 获取角色ID
        $roleId = $input->getRoleId();

        // 步骤2: 通过 Repository 获取 Model
        /** @var null|Role $roleModel */
        $roleModel = $this->roleRepository->findById($roleId);
        if (! $roleModel) {
            throw new \RuntimeException("角色不存在: ID={$roleId}");
        }

        // 步骤3: 通过 Mapper 将 Model 转换为 Entity
        $roleEntity = RoleMapper::fromModel($roleModel);

        // 步骤4: 检查是否为超级管理员角色
        $isSuperAdmin = $roleEntity->isSuperAdmin();

        // 步骤5: 处理权限代码
        $permissionCodes = $input->getPermissionCodes();

        if (empty($permissionCodes)) {
            // 清空权限
            $result = $roleEntity->grantPermissions([], $isSuperAdmin);
            if ($result->shouldDetach) {
                $roleModel->menus()->detach();
            }
            return;
        }

        // 步骤6: 获取菜单ID
        $menuIds = $this->menuRepository
            ->listByCodes($permissionCodes)
            ->pluck('id')
            ->toArray();

        // 步骤7: 调用 Entity 的行为方法
        $result = $roleEntity->grantPermissions($menuIds, $isSuperAdmin);

        // 步骤8: 执行持久化操作
        if ($result->success) {
            $roleModel->menus()->sync($result->menuIds);
        }
    }
}
