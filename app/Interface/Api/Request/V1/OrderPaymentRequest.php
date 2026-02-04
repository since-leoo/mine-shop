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

namespace App\Interface\Api\Request\V1;

use App\Domain\Order\Enum\OrderStatus;
use App\Interface\Common\Request\BaseRequest;
use Hyperf\Validation\Rule;

class OrderPaymentRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'order_no' => ['required', 'string', Rule::exists('orders', 'order_no')
                ->where('status', OrderStatus::PENDING->value)
                ->where('member_id', memberId()),
            ],
            'pay_method' => ['required', 'string', 'in:wechat,balance'],
        ];
    }

    public function attributes(): array
    {
        return [
            'order_no' => '订单号',
            'pay_method' => '支付方式',
        ];
    }
}
