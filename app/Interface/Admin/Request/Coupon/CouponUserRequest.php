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

use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\Validation\Rule;

class CouponUserRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function listRules(): array
    {
        return [
            'coupon_id' => ['nullable', 'integer', 'min:1'],
            'member_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::in(['unused', 'used', 'expired'])],
            'keyword' => ['nullable', 'string', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function markUsedRules(): array
    {
        return [];
    }

    public function markExpiredRules(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [
            'coupon_id' => '优惠券ID',
            'member_id' => '会员ID',
            'status' => '状态',
            'keyword' => '搜索关键词',
        ];
    }
}
