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
use Plugin\Distribution\Domain\Contract\WithdrawalInput;
use Plugin\Distribution\Interface\Dto\WithdrawalDto;

final class WithdrawalRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['amount' => ['required', 'integer', 'between:1,100000000'], 'channel' => ['required', 'in:manual'], 'account_name' => ['required', 'string', 'max:80'], 'account_no' => ['required', 'string', 'max:160'], 'remark' => ['nullable', 'string', 'max:500'], 'request_key' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/']];
    }

    public function toDto(): WithdrawalInput
    {
        return new WithdrawalDto($this->validated());
    }
}
