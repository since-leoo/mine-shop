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

use App\Interface\Admin\Dto\Permission\UserGrantRolesDto;
use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\DTO\Mapper;

class BatchGrantRolesForUserRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function batchGrantRolesForUserRules(): array
    {
        return [
            'role_codes' => 'required|array',
            'role_codes.*' => 'string|exists:role,code',
        ];
    }

    public function attributes(): array
    {
        return [
            'role_codes' => trans('role.code'),
        ];
    }

    public function toDto(int $userId, int $operatorId): UserGrantRolesDto
    {
        $params = $this->validated();
        $params['user_id'] = $userId;
        $params['operator_id'] = $operatorId;
        return Mapper::map($params, new UserGrantRolesDto());
    }
}
