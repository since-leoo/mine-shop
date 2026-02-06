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

use App\Domain\Permission\Contract\Role\RoleGrantPermissionsInput;
use App\Domain\Permission\Entity\RoleEntity;
use App\Domain\Permission\Service\RoleService;
use App\Infrastructure\Model\Permission\Role;
use Hyperf\DbConnection\Db;

final class RoleCommandService
{
    public function __construct(private readonly RoleService $roleService) {}

    public function create(RoleEntity $entity): Role
    {
        return Db::transaction(fn () => $this->roleService->create($entity));
    }

    public function update(int $id, RoleEntity $entity): bool
    {
        return Db::transaction(fn () => $this->roleService->update($id, $entity));
    }

    /**
     * @param array<int> $ids
     */
    public function delete(array $ids): int
    {
        return $this->roleService->delete($ids);
    }

    public function grantPermissions(RoleGrantPermissionsInput $input): void
    {
        $this->roleService->grantPermissions($input);
    }
}
