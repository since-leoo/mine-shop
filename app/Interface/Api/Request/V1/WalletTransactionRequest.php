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

class WalletTransactionRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'wallet_type' => ['required', 'string', 'in:balance,points'],
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'wallet_type' => '钱包类型',
            'page' => '页码',
            'page_size' => '每页数量',
        ];
    }
}
