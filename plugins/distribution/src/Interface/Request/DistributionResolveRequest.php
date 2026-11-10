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

namespace Plugin\Distribution\Interface\Request;

use App\Interface\Common\Request\BaseRequest;

final class DistributionResolveRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['token' => ['nullable', 'string', 'max:32', 'regex:/^[a-f0-9]+$/'], 'invite_code' => ['nullable', 'string', 'max:32', 'regex:/^[A-Za-z0-9]+$/']];
    }
}
