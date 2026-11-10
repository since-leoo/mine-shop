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
use Plugin\Distribution\Domain\Contract\WithdrawalReviewInput;
use Plugin\Distribution\Interface\Dto\WithdrawalReviewDto;

final class WithdrawalReviewRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['action' => ['required', 'in:approve,reject,pay,fail'], 'remark' => ['nullable', 'string', 'max:500'], 'payment_no' => ['nullable', 'string', 'max:120']];
    }

    public function toDto(): WithdrawalReviewInput
    {
        return new WithdrawalReviewDto($this->validated());
    }
}
