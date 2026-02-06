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

use App\Domain\Seckill\Contract\SeckillSessionInput;
use App\Interface\Admin\DTO\Seckill\SeckillSessionDto;
use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\DTO\Mapper;
use Hyperf\Validation\Rule;

class SeckillSessionRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function listRules(): array
    {
        return [
            'activity_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::in(['pending', 'active', 'ended', 'cancelled', 'sold_out'])],
            'is_enabled' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function storeRules(): array
    {
        return [
            'activity_id' => ['required', 'integer', 'min:1', 'exists:seckill_activities,id'],
            'start_time' => ['required', 'date', 'after:now'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'status' => ['nullable', Rule::in(['pending', 'active', 'ended', 'cancelled', 'sold_out'])],
            'max_quantity_per_user' => ['nullable', 'integer', 'min:1'],
            'total_quantity' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_enabled' => ['nullable', 'boolean'],
            'rules' => ['nullable', 'array'],
            'remark' => ['nullable', 'string'],
        ];
    }

    public function updateRules(): array
    {
        return [
            'activity_id' => ['nullable', 'integer', 'min:1', 'exists:seckill_activities,id'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'status' => ['nullable', Rule::in(['pending', 'active', 'ended', 'cancelled', 'sold_out'])],
            'max_quantity_per_user' => ['nullable', 'integer', 'min:1'],
            'total_quantity' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_enabled' => ['nullable', 'boolean'],
            'rules' => ['nullable', 'array'],
            'remark' => ['nullable', 'string'],
        ];
    }

    public function toggleStatusRules(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [
            'activity_id' => '活动ID',
            'start_time' => '开始时间',
            'end_time' => '结束时间',
            'status' => '场次状态',
            'max_quantity_per_user' => '每人限购数量',
            'total_quantity' => '场次总库存',
            'sort_order' => '排序',
            'is_enabled' => '是否启用',
            'rules' => '场次规则',
            'remark' => '备注',
        ];
    }

    public function messages(): array
    {
        return [
            'activity_id.required' => '活动ID不能为空',
            'activity_id.exists' => '活动不存在',
            'start_time.required' => '开始时间不能为空',
            'start_time.after' => '开始时间必须晚于当前时间',
            'end_time.required' => '结束时间不能为空',
            'end_time.after' => '结束时间必须晚于开始时间',
        ];
    }

    /**
     * 转换为DTO.
     */
    public function toDto(?int $id = null): SeckillSessionInput
    {
        $params = $this->validated();
        $params['id'] = $id;

        return Mapper::map($params, new SeckillSessionDto());
    }
}
