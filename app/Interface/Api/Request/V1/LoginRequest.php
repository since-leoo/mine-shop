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

class LoginRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
            'encrypted_data' => ['required', 'string'],
            'iv' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => '授权 code',
            'encrypted_data' => '加密数据',
            'iv' => '初始向量',
        ];
    }
}
