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

class PhoneAuthorizeRequest extends BaseRequest
{
    public function bindPhoneRules(): array
    {
        return [
            'code' => 'required|string',
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => '凭证',
        ];
    }
}
