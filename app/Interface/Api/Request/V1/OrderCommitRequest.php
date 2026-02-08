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

use App\Domain\Trade\Order\Contract\OrderSubmitInput;
use App\Interface\Api\DTO\Order\OrderCommitDto;

final class OrderCommitRequest extends OrderPreviewRequest
{
    public function rules(): array
    {
        $base = parent::rules();

        return array_merge($base, [
            'order_type' => ['required', 'string', 'in:normal,seckill,group_buy'],
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
        $dto->coupon_id = isset($params['coupon_id']) ? (int) $params['coupon_id'] : null;
        $dto->store_info_list = $params['store_info_list'] ?? null;
        $dto->total_amount = $params['total_amount'];
        $dto->user_name = $params['user_name'] ?? null;
        $dto->activity_id = isset($params['activity_id']) ? (int) $params['activity_id'] : null;
        $dto->session_id = isset($params['session_id']) ? (int) $params['session_id'] : null;
        $dto->group_buy_id = isset($params['group_buy_id']) ? (int) $params['group_buy_id'] : null;
        $dto->group_no = $params['group_no'] ?? null;
        return $dto;
    }
}
