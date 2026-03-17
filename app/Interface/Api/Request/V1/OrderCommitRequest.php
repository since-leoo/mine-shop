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
use Hyperf\DTO\Mapper;

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
        $params = $this->validated();
        $params['member_id'] = $memberId;
        $params['coupon_id'] = isset($params['coupon_id']) ? (int) $params['coupon_id'] : null;
        $params['activity_id'] = isset($params['activity_id']) ? (int) $params['activity_id'] : null;
        $params['session_id'] = isset($params['session_id']) ? (int) $params['session_id'] : null;
        $params['group_buy_id'] = isset($params['group_buy_id']) ? (int) $params['group_buy_id'] : null;
        $params['buy_original_price'] = ! empty($params['buy_original_price']);
        $params['from_cart'] = ! empty($params['from_cart']);

        return Mapper::map($params, new OrderCommitDto());
    }
}
