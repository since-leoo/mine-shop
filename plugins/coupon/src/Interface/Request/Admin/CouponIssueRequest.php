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

namespace Plugin\Since\Coupon\Interface\Request\Admin;

use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;

class CouponIssueRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function rules(): array
    {
        return [
            'member_ids' => ['required', 'array', 'min:1'],
            'member_ids.*' => ['integer', 'min:1'],
            'expire_at' => ['nullable', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'member_ids' => '会员ID列表',
            'expire_at' => '过期时间',
        ];
    }
}
