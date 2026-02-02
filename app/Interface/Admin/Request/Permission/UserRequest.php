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

use App\Domain\Permission\Enum\DataPermission\PolicyType;
use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\Validation\Rule;

class UserRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function updateInfoRules(): array
    {
        return $this->baseRules();
    }

    public function createRules(): array
    {
        return array_merge($this->baseRules(), [
            'password' => ['required', 'string', 'min:6'],
        ]);
    }

    public function saveRules(): array
    {
        return $this->baseRules();
    }

    public function attributes(): array
    {
        return [
            'username' => trans('user.username'),
            'user_type' => trans('user.user_type'),
            'nickname' => trans('user.nickname'),
            'phone' => trans('user.phone'),
            'email' => trans('user.email'),
            'avatar' => trans('user.avatar'),
            'signed' => trans('user.signed'),
            'status' => trans('user.status'),
            'backend_setting' => trans('user.backend_setting'),
            'created_by' => trans('user.created_by'),
            'remark' => trans('user.remark'),
            'department' => trans('user.department'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function baseRules(): array
    {
        return [
            'username' => 'required|string|max:20',
            'user_type' => 'required|integer',
            'nickname' => ['required', 'string', 'max:60', 'regex:/^[^\s]+$/'],
            'phone' => 'sometimes|string|max:12',
            'email' => 'sometimes|string|max:60|email:rfc,dns',
            'avatar' => 'sometimes|string|max:255|url',
            'signed' => 'sometimes|string|max:255',
            'status' => 'sometimes|integer',
            'backend_setting' => 'sometimes|array|max:255',
            'remark' => 'sometimes|string|max:255',
            'policy' => 'sometimes|array',
            'policy.policy_type' => [
                'required_with:policy',
                'string',
                'max:20',
                Rule::enum(PolicyType::class),
            ],
            'policy.value' => [
                'sometimes',
            ],
            'department' => [
                'sometimes',
                'array',
            ],
            'department.*' => [
                'required_with:department',
                'integer',
                'exists:department,id',
            ],
            'position' => [
                'sometimes',
                'array',
            ],
            'position.*' => [
                'sometimes',
                'integer',
                'exists:position,id',
            ],
        ];
    }
}
