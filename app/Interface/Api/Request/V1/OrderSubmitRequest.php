<?php

declare(strict_types=1);

namespace App\Interface\Api\Request\V1;

use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;

final class OrderSubmitRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function submitRules(): array
    {
        return [
            'member_id' => ['required', 'integer', 'min:1'],
            'order_type' => ['required', 'string', 'max:32'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sku_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'address' => ['required', 'array'],
            'address.name' => ['required', 'string', 'max:30'],
            'address.phone' => ['required', 'string', 'max:20'],
            'address.province' => ['required', 'string', 'max:50'],
            'address.city' => ['required', 'string', 'max:50'],
            'address.district' => ['required', 'string', 'max:50'],
            'address.detail' => ['required', 'string', 'max:200'],
            'coupon_ids' => ['nullable', 'array'],
            'coupon_ids.*' => ['integer', 'min:1'],
            'remark' => ['nullable', 'string', 'max:255'],
            'extra' => ['nullable', 'array'],
        ];
    }
}
