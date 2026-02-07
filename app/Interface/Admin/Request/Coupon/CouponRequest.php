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

namespace App\Interface\Admin\Request\Coupon;

use App\Domain\Coupon\Contract\CouponInput;
use App\Interface\Admin\Dto\Coupon\CouponDto;
use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\DTO\Mapper;
use Hyperf\Validation\Rule;

class CouponRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function listRules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', Rule::in(['fixed', 'percent'])],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date', 'after:start_time'],
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function storeRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(['fixed', 'percent'])],
            'value' => ['required', 'numeric', 'min:0.01'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'total_quantity' => ['required', 'integer', 'min:1'],
            'per_user_limit' => ['nullable', 'integer', 'min:1'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function updateRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(['fixed', 'percent'])],
            'value' => ['required', 'numeric', 'min:0.01'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'total_quantity' => ['required', 'integer', 'min:1'],
            'per_user_limit' => ['nullable', 'integer', 'min:1'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function toggleStatusRules(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [
            'name' => '优惠券名称',
            'type' => '优惠类型',
            'value' => '优惠值',
            'min_amount' => '最低消费金额',
            'total_quantity' => '发放总数',
            'per_user_limit' => '每人限领',
            'start_time' => '开始时间',
            'end_time' => '结束时间',
            'status' => '状态',
            'description' => '描述',
        ];
    }

    /**
     * 转换为DTO.
     */
    public function toDto(?int $id = null): CouponInput
    {
        $params = $this->validated();
        $params['id'] = $id;

        // Convert snake_case keys to camelCase for DTO mapping
        if (isset($params['min_amount'])) {
            $params['minAmount'] = $params['min_amount'];
            unset($params['min_amount']);
        }

        if (isset($params['total_quantity'])) {
            $params['totalQuantity'] = $params['total_quantity'];
            unset($params['total_quantity']);
        }

        if (isset($params['per_user_limit'])) {
            $params['perUserLimit'] = $params['per_user_limit'];
            unset($params['per_user_limit']);
        }

        if (isset($params['start_time'])) {
            $params['startTime'] = $params['start_time'];
            unset($params['start_time']);
        }

        if (isset($params['end_time'])) {
            $params['endTime'] = $params['end_time'];
            unset($params['end_time']);
        }

        return Mapper::map($params, new CouponDto());
    }
}
