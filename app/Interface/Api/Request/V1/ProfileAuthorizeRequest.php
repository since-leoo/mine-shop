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

class ProfileAuthorizeRequest extends BaseRequest
{
    public function authorizeProfileRule(): array
    {
        return [
            'avatar_url' => 'required|url',
            'nickname' => 'required|string',
        ];
    }

    public function attributes(): array
    {
        return [
            'avatar_url' => '头像',
            'nickname' => '昵称',
        ];
    }
}
