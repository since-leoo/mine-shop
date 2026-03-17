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

use App\Domain\Trade\Order\Contract\OrderPreviewInput;
use App\Interface\Api\DTO\Order\OrderPreviewDto;
use App\Interface\Common\Request\BaseRequest;
use Hyperf\DTO\Mapper;

class OrderPreviewRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'goods_request_list' => ['required', 'array', 'min:1'],
            'goods_request_list.*.sku_id' => ['required', 'integer', 'min:1'],
            'goods_request_list.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'order_type' => ['nullable', 'string', 'in:normal,seckill,group_buy'],
            'group_buy_id' => ['required_if:order_type,group_buy', 'integer', 'min:1'],
            'group_no' => ['nullable', 'string', 'max:32'],
            'address_id' => ['nullable', 'integer', 'min:1'],
            'user_address' => ['nullable', 'array'],
            'user_address.name' => ['nullable', 'string', 'max:60'],
            'user_address.phone' => ['nullable', 'string', 'max:20'],
            'user_address.province' => ['nullable', 'string', 'max:30'],
            'user_address.city' => ['nullable', 'string', 'max:30'],
            'user_address.district' => ['nullable', 'string', 'max:30'],
            'user_address.detail' => ['nullable', 'string', 'max:200'],
            'coupon_id' => ['nullable', 'integer', 'min:1'],
            'store_info_list' => ['nullable', 'array'],
            'store_info_list.*.remark' => ['nullable', 'string', 'max:200'],
            'activity_id' => ['required_if:order_type,seckill', 'integer', 'min:1'],
            'session_id' => ['required_if:order_type,seckill', 'integer', 'min:1'],
            'buy_original_price' => ['nullable', 'boolean'],
            'from_cart' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'goods_request_list' => '商品列表',
            'goods_request_list.*.sku_id' => 'SKU',
            'goods_request_list.*.quantity' => '数量',
            'address_id' => '地址ID',
        ];
    }

    public function toDto(int $memberId): OrderPreviewInput
    {
        $params = $this->validated();
        $params['member_id'] = $memberId;
        $params['order_type'] = $params['order_type'] ?? 'normal';
        $params['coupon_id'] = isset($params['coupon_id']) ? (int) $params['coupon_id'] : null;
        $params['activity_id'] = isset($params['activity_id']) ? (int) $params['activity_id'] : null;
        $params['session_id'] = isset($params['session_id']) ? (int) $params['session_id'] : null;
        $params['group_buy_id'] = isset($params['group_buy_id']) ? (int) $params['group_buy_id'] : null;
        $params['buy_original_price'] = ! empty($params['buy_original_price']);
        $params['from_cart'] = ! empty($params['from_cart']);

        return Mapper::map($params, new OrderPreviewDto());
    }
}
