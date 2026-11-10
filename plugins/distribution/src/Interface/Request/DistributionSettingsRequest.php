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
use Plugin\Distribution\Domain\Contract\DistributionSettingsInput;
use Plugin\Distribution\Interface\Dto\DistributionSettingsDto;

final class DistributionSettingsRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'], 'h5_enabled' => ['required', 'boolean'], 'mini_program_enabled' => ['required', 'boolean'],
            'commission_rate_bps' => ['required', 'integer', 'between:0,10000'], 'settlement_days' => ['required', 'integer', 'between:0,365'],
            'withdrawal_min_amount' => ['required', 'integer', 'between:1,100000000'], 'withdrawal_fee_bps' => ['required', 'integer', 'between:0,9999'],
            'commission_mode' => ['required', 'in:all_orders,first_order'], 'activity_enabled' => ['required', 'boolean'],
            'excluded_product_ids' => ['present', 'array', 'max:1000'], 'excluded_product_ids.*' => ['integer', 'min:1', 'distinct'],
            'product_rules' => ['present', 'array', 'max:1000'], 'product_rules.*.product_id' => ['required', 'integer', 'min:1'],
            'product_rules.*.sku_id' => ['nullable', 'integer', 'min:1'], 'product_rules.*.mode' => ['required', 'in:rate,fixed'],
            'product_rules.*.value' => ['required', 'integer', 'between:0,100000000'], 'h5_base_url' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function toDto(): DistributionSettingsInput
    {
        $data = $this->validated();
        $data['h5_base_url'] ??= '';
        return new DistributionSettingsDto($data);
    }
}
