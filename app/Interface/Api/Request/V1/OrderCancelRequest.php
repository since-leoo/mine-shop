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

use App\Interface\Common\Request\BaseRequest;

class OrderCancelRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'order_no' => ['required', 'string', 'max:32'],
        ];
    }

    public function attributes(): array
    {
        return [
            'order_no' => '订单编号',
        ];
    }
}
