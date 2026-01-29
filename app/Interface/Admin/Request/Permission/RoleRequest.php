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

class RoleRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function createRules(): array
    {
        return [
            'name' => 'required|string|max:60',
            'code' => [
                'required',
                'string',
                'max:60',
                'regex:/^[a-zA-Z0-9_]+$/',
                'unique:role,code',
            ],
            'status' => 'sometimes|integer|in:1,2',
            'sort' => 'required|integer',
            'remark' => 'nullable|string|max:255',
        ];
    }

    public function saveRules(): array
    {
        return [
            'name' => 'required|string|max:60',
            'code' => [
                'required',
                'string',
                'max:60',
                'regex:/^[a-zA-Z0-9_]+$/',
                'unique:role,code,' . $this->route('id'),
            ],
            'status' => 'sometimes|integer|in:1,2',
            'sort' => 'required|integer',
            'remark' => 'nullable|string|max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => trans('role.name'),
            'code' => trans('role.code'),
            'status' => trans('role.status'),
            'sort' => trans('role.sort'),
            'remark' => trans('role.remark'),
        ];
    }
}
