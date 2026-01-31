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

class UserRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function loginRules(): array
    {
        return [
            'username' => 'required|string|max:16',
            'password' => 'required|string|max:32',
        ];
    }

    public function attributes(): array
    {
        return [
            'username' => trans('user.username'),
            'password' => trans('user.password'),
        ];
    }
}
