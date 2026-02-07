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

use App\Domain\Permission\Contract\Common\DeleteInput;
use App\Domain\Permission\Contract\Role\RoleGrantPermissionsInput;
use App\Domain\Permission\Contract\Role\RoleInput;
use App\Domain\Permission\Service\RoleService;
use App\Infrastructure\Model\Permission\Role;
use Hyperf\DbConnection\Db;

final class RoleCommandService
{
    public function __construct(private readonly RoleService $roleService) {}

    public function create(RoleInput $input): Role
    {
        return Db::transaction(fn () => $this->roleService->create($input));
    }

    public function update(int $id, RoleInput $input): bool
    {
        return Db::transaction(fn () => $this->roleService->update($id, $input));
    }

    public function delete(DeleteInput $input): int
    {
        return $this->roleService->delete($input->getIds());
    }

    public function grantPermissions(RoleGrantPermissionsInput $input): void
    {
        $this->roleService->grantPermissions($input);
    }
}
