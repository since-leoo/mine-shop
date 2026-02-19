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

/**
 * 角色领域服务.
 *
 * 负责角色的核心业务逻辑，只接受实体对象。
 * DTO 到实体的转换由应用层负责。
 */
final class DomainRoleService extends IService
{
    public function __construct(
        protected readonly RoleRepository $repository,
        protected readonly MenuRepository $menuRepository
    ) {}

    /**
     * 创建角色.
     *
     * @param RoleEntity $entity 角色实体
     * @return Role 创建的模型
     */
    public function create(RoleEntity $entity): Role
    {
        return $this->repository->create($entity->toArray());
    }

    /**
     * 更新角色.
     *
     * @param RoleEntity $entity 更新后的实体
     * @return bool 是否更新成功
     */
    public function update(RoleEntity $entity): bool
    {
        return $this->repository->updateById($entity->getId(), $entity->toArray());
    }

    /**
     * 批量删除角色.
     *
     * @param array<int> $ids 角色 ID 数组
     * @return int 删除的记录数
     */
    public function delete(array $ids): int
    {
        return $this->repository->deleteByIds($ids);
    }

    /**
     * 获取角色实体.
     *
     * @param int $id 角色 ID
     * @return RoleEntity 角色实体
     * @throws \RuntimeException 角色不存在时抛出
     */
    public function getEntity(int $id): RoleEntity
    {
        /** @var null|Role $model */
        $model = $this->repository->findById($id);
        if (! $model) {
            throw new \RuntimeException("角色不存在: ID={$id}");
        }
        return RoleMapper::fromModel($model);
    }

    /**
     * 授予权限给角色.
     *
     * @param RoleGrantPermissionsInput $input 权限授权输入对象
     */
    public function grantPermissions(RoleGrantPermissionsInput $input): void
    {
        $roleId = $input->getRoleId();

        /** @var null|Role $roleModel */
        $roleModel = $this->repository->findById($roleId);
        if (! $roleModel) {
            throw new \RuntimeException("角色不存在: ID={$roleId}");
        }

        $roleEntity = RoleMapper::fromModel($roleModel);
        $isSuperAdmin = $roleEntity->isSuperAdmin();
        $permissionCodes = $input->getPermissionCodes();

        if (empty($permissionCodes)) {
            $result = $roleEntity->grantPermissions([], $isSuperAdmin);
            if ($result->shouldDetach) {
                $roleModel->menus()->detach();
            }
            return;
        }

        $menuIds = $this->menuRepository
            ->listByCodes($permissionCodes)
            ->pluck('id')
            ->toArray();

        $result = $roleEntity->grantPermissions($menuIds, $isSuperAdmin);

        if ($result->success) {
            $roleModel->menus()->sync($result->menuIds);
        }
    }
}
