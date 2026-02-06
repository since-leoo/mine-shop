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

namespace App\Interface\Admin\Request\Permission;

use App\Interface\Admin\Dto\Permission\RoleGrantPermissionsDto;
use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\DTO\Mapper;

class BatchGrantPermissionsForRoleRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function batchGrantPermissionsForRoleRules(): array
    {
        return [
            'permissions' => 'sometimes|array',
            'permissions.*' => 'string|exists:menu,name',
        ];
    }

    public function attributes(): array
    {
        return [
            'permissions' => trans('menu.name'),
        ];
    }

    public function toDto(int $roleId, int $operatorId): RoleGrantPermissionsDto
    {
        $params = $this->validated();
        // allow empty permissions -> detach all
        $params['permissions'] ??= [];
        $params['role_id'] = $roleId;
        $params['operator_id'] = $operatorId;
        return Mapper::map($params, new RoleGrantPermissionsDto());
    }
}
