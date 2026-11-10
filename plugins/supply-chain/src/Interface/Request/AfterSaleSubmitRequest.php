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
use Plugin\SupplyChain\Domain\Contract\AfterSaleInput;
use Plugin\SupplyChain\Interface\Dto\AfterSaleSubmitDto;

final class AfterSaleSubmitRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'external_order_item_id' => ['required', 'string', 'max:128'],
            'quantity' => ['required', 'integer', 'between:1,1000000'],
            'amount_cents' => ['required', 'integer', 'between:0,2147483647'],
            'freight_cents' => ['sometimes', 'integer', 'between:0,2147483647'],
            'technical_service_fee_cents' => ['sometimes', 'integer', 'between:0,2147483647'],
            'reason_type' => ['required', 'integer', 'between:0,2147483647'],
            'reason' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            // No guessed supplier defaults. Supported values are verified again by the adapter.
            'received_status' => ['required', 'integer', 'between:0,100'],
            'refund_type' => ['required', 'integer', 'between:0,100', 'not_in:2'],
            'refund_way' => ['required', 'integer', 'between:0,100'],
        ];
    }

    public function toDto(): AfterSaleInput
    {
        return new AfterSaleSubmitDto($this->validated());
    }
}
