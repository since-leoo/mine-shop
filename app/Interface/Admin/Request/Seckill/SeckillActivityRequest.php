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

namespace App\Interface\Admin\Request\Seckill;

use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\Validation\Rule;

class SeckillActivityRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function listRules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:100'],
            'keyword' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['pending', 'active', 'ended', 'cancelled'])],
            'is_enabled' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function storeRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', Rule::in(['pending', 'active', 'ended', 'cancelled'])],
            'is_enabled' => ['nullable', 'boolean'],
            'rules' => ['nullable', 'array'],
            'remark' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function updateRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', Rule::in(['pending', 'active', 'ended', 'cancelled'])],
            'is_enabled' => ['nullable', 'boolean'],
            'rules' => ['nullable', 'array'],
            'remark' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function toggleStatusRules(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [
            'title' => '活动标题',
            'description' => '活动描述',
            'status' => '活动状态',
            'is_enabled' => '是否启用',
            'rules' => '活动规则',
            'remark' => '备注',
        ];
    }
}
