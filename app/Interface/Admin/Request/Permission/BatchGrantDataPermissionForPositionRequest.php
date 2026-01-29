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

class BatchGrantDataPermissionForPositionRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function batchDataPermissionRules(): array
    {
        return [
            'policy_type' => [
                'required',
                'string',
                Rule::enum(PolicyType::class),
            ],
            'value' => [
                'sometimes',
                'array',
                'min:1',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'policy_type' => '策略类型',
            'value' => '策略值',
        ];
    }
}
