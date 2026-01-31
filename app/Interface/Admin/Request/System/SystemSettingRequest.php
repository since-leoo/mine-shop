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

namespace App\Interface\Admin\Request\System;

use App\Interface\Common\Request\BaseRequest;

final class SystemSettingRequest extends BaseRequest
{
    public function updateRules(): array
    {
        return [
            'value' => ['nullable'],
        ];
    }

    public function updateAttributes(): array
    {
        return [
            'value' => '配置值',
        ];
    }
}
