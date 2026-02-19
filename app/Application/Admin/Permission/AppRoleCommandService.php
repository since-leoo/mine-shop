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

use App\Domain\Permission\Contract\Common\DeleteInput;
use App\Domain\Permission\Contract\Role\RoleGrantPermissionsInput;
use App\Domain\Permission\Contract\Role\RoleInput;
use App\Domain\Permission\Mapper\RoleMapper;
use App\Domain\Permission\Service\DomainRoleService;
use App\Infrastructure\Model\Permission\Role;
use Hyperf\DbConnection\Db;

/**
 * 角色应用层命令服务.
 *
 * 负责协调领域服务，处理 DTO 到实体的转换。
 */
final class AppRoleCommandService
{
    public function __construct(private readonly DomainRoleService $roleService) {}

    /**
     * 创建角色.
     *
     * @param RoleInput $input 角色输入 DTO
     * @return Role 创建的角色模型
     */
    public function create(RoleInput $input): Role
    {
        // 使用 Mapper 将 DTO 转换为实体
        $entity = RoleMapper::fromDto($input);
        return Db::transaction(fn () => $this->roleService->create($entity));
    }

    /**
     * 更新角色.
     *
     * @param int $id 角色 ID
     * @param RoleInput $input 角色输入 DTO
     * @return bool 是否更新成功
     */
    public function update(int $id, RoleInput $input): bool
    {
        // 从数据库获取实体并更新
        $entity = $this->roleService->getEntity($id);
        $entity->setName($input->getName());
        $entity->setCode($input->getCode());
        $entity->setStatus($input->getStatus());
        $entity->setSort($input->getSort());
        $entity->setRemark($input->getRemark());
        $entity->setUpdatedBy($input->getUpdatedBy());
        return Db::transaction(fn () => $this->roleService->update($entity));
    }

    /**
     * 删除角色.
     *
     * @param DeleteInput $input 删除输入
     * @return int 删除的记录数
     */
    public function delete(DeleteInput $input): int
    {
        return $this->roleService->delete($input->getIds());
    }

    /**
     * 授予权限给角色.
     *
     * @param RoleGrantPermissionsInput $input 权限授权输入
     */
    public function grantPermissions(RoleGrantPermissionsInput $input): void
    {
        $this->roleService->grantPermissions($input);
    }
}
