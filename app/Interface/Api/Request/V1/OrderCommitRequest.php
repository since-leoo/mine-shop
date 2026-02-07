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

use App\Domain\Order\Contract\OrderSubmitInput;
use App\Interface\Api\DTO\Order\OrderCommitDto;

final class OrderCommitRequest extends OrderPreviewRequest
{
    public function rules(): array
    {
        $base = parent::rules();

        return array_merge($base, [
            'order_type' => ['required', 'string', 'in:normal'],
            'total_amount' => ['required', 'integer', 'min:0'],
            'user_name' => ['nullable', 'string', 'max:60'],
            'invoice_request' => ['nullable', 'array'],
        ]);
    }

    public function toDto(int $memberId): OrderSubmitInput
    {
        $dto = new OrderCommitDto();
        $params = $this->validated();
        $dto->member_id = $memberId;
        $dto->order_type = $params['order_type'];
        $dto->goods_request_list = $params['goods_request_list'];
        $dto->address_id = $params['address_id'] ?? null;
        $dto->user_address = $params['user_address'] ?? null;
        $dto->coupon_list = $params['coupon_list'] ?? null;
        $dto->store_info_list = $params['store_info_list'] ?? null;
        $dto->total_amount = $params['total_amount'];
        $dto->user_name = $params['user_name'] ?? null;
        return $dto;
    }
}
