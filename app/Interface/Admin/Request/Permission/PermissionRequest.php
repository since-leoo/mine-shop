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

class PermissionRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function updateRules(): array
    {
        return [
            'nickname' => 'sometimes|string|max:255',
            'new_password' => 'sometimes|confirmed|string|min:8',
            'new_password_confirmation' => 'sometimes|string|min:8',
            'old_password' => ['sometimes', 'string'],
            'avatar' => 'sometimes|string|max:255',
            'signed' => 'sometimes|string|max:255',
            'backend_setting' => 'sometimes|array',
        ];
    }

    public function attributes(): array
    {
        return [
            'nickname' => trans('user.nickname'),
            'new_password' => trans('user.password'),
            'new_password_confirmation' => trans('user.password_confirmation'),
            'old_password' => trans('user.old_password'),
            'avatar' => trans('user.avatar'),
            'signed' => trans('user.signed'),
            'backend_setting' => trans('user.backend_setting'),
        ];
    }
}
