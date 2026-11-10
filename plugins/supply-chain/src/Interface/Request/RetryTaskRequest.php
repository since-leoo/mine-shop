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

namespace Plugin\SupplyChain\Interface\Request;

use App\Interface\Common\Request\BaseRequest;

final class RetryTaskRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:500', 'regex:/\S/u']];
    }

    public function reason(): string
    {
        return trim((string) $this->validated()['reason']);
    }
}
