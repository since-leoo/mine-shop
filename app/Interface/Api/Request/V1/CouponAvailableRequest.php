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
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;

final class CouponAvailableRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function availableRules(): array
    {
        return [
            'spu_id' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function availableAttributes(): array
    {
        return [
            'spu_id' => '商品ID',
            'limit' => '返回条数',
        ];
    }
}
