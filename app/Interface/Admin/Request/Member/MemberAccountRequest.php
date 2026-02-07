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

namespace App\Interface\Admin\Request\Member;

use App\Domain\Member\Contract\MemberWalletInput;
use App\Interface\Admin\Dto\Member\MemberWalletDto;
use App\Interface\Common\Request\BaseRequest;
use Hyperf\DTO\Mapper;
use Hyperf\Validation\Rule;

class MemberAccountRequest extends BaseRequest
{
    public function adjustRules(): array
    {
        return [
            'member_id' => ['required', 'integer', 'min:1'],
            'value' => ['required', 'numeric', 'between:-1000000,1000000000000', 'not_in:0'],
            'source' => ['nullable', 'string', 'max:50'],
            'type' => ['required', Rule::in(['balance', 'points'])],
            'remark' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function walletLogsRules(): array
    {
        return [
            'member_id' => ['nullable', 'integer', 'min:1'],
            'wallet_type' => ['nullable', Rule::in(['balance', 'points'])],
            'source' => ['nullable', 'string', 'max:50'],
            'operator_type' => ['nullable', Rule::in(['admin', 'system', 'member'])],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'member_id' => '会员ID',
            'value' => '变动额度',
            'source' => '来源',
            'type' => '钱包类型',
            'wallet_type' => '钱包类型',
            'remark' => '备注',
        ];
    }

    /**
     * 转换为 DTO.
     * @param int $operatorId 操作者ID
     */
    public function toDto(int $operatorId): MemberWalletInput
    {
        $params = $this->validated();
        $params['operator_id'] = $operatorId;

        return Mapper::map($params, new MemberWalletDto());
    }
}
