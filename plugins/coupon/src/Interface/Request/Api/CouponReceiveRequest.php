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

namespace Plugin\Since\Coupon\Interface\Request\Api;

use Hyperf\Validation\Request\FormRequest;

final class CouponReceiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'coupon_id' => 'required|integer|min:1',
        ];
    }

    public function attributes(): array
    {
        return [
            'coupon_id' => '优惠券ID',
        ];
    }
}
