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

use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;

class DepartmentRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function createRules(): array
    {
        return [
            'name' => 'required|string|max:60|unique:department,name',
            'parent_id' => 'sometimes|integer',
            'department_users' => 'sometimes|array',
            'department_users.*' => 'sometimes|integer',
            'leader' => 'sometimes|array',
            'leader.*' => 'sometimes|integer',
        ];
    }

    public function saveRules(): array
    {
        return [
            'name' => 'required|string|max:60|unique:department,name,' . $this->route('id'),
            'parent_id' => 'sometimes|integer',
            'department_users' => 'sometimes|array',
            'department_users.*' => 'sometimes|integer',
            'leader' => 'sometimes|array',
            'leader.*' => 'sometimes|integer',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '部门名称',
            'parent_id' => '上级部门',
        ];
    }
}
