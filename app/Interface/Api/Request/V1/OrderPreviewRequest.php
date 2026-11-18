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
            'user_address.name' => ['required_with:user_address', 'string', 'max:50'],
            'user_address.phone' => ['required_with:user_address', 'string', 'max:20'],
            'user_address.province' => ['required_with:user_address', 'string', 'max:50'],
            'user_address.city' => ['required_with:user_address', 'string', 'max:50'],
            'user_address.district' => ['required_with:user_address', 'string', 'max:50'],
            'user_address.detail' => ['required_with:user_address', 'string', 'max:255'],
            'user_address.full_address' => ['nullable', 'string', 'max:500'],
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
            'user_address.name' => '收货人',
            'user_address.phone' => '收货电话',
            'user_address.province' => '收货省份',
            'user_address.city' => '收货城市',
            'user_address.district' => '收货区县',
            'user_address.detail' => '收货详细地址',
            'user_address.full_address' => '完整收货地址',
        ];
    }

    protected function validationData(): array
    {
        $data = parent::validationData();
        $address = $data['user_address'] ?? null;
        if (\is_array($address)) {
            // 地址簿和结算预览使用不同的展示字段，统一后再验证、生成订单快照。
            $data['user_address'] = [
                'name' => $address['name'] ?? null,
                'phone' => $address['phone'] ?? $address['phone_number'] ?? null,
                'province' => $address['province'] ?? $address['province_name'] ?? null,
                'city' => $address['city'] ?? $address['city_name'] ?? null,
                'district' => $address['district'] ?? $address['district_name'] ?? null,
                'detail' => $address['detail'] ?? $address['detail_address'] ?? null,
                'full_address' => $address['full_address'] ?? $address['address'] ?? null,
            ];
        }
        return $data;
    }

    public function toDto(int $memberId): OrderPreviewInput
    {
        $params = $this->validated();
        $params['member_id'] = $memberId;
        $params['order_type'] ??= 'normal';
        $params['coupon_id'] = isset($params['coupon_id']) ? (int) $params['coupon_id'] : null;
        $params['activity_id'] = isset($params['activity_id']) ? (int) $params['activity_id'] : null;
        $params['session_id'] = isset($params['session_id']) ? (int) $params['session_id'] : null;
        $params['group_buy_id'] = isset($params['group_buy_id']) ? (int) $params['group_buy_id'] : null;
        $params['buy_original_price'] = ! empty($params['buy_original_price']);
        $params['from_cart'] = ! empty($params['from_cart']);

        return Mapper::map($params, new OrderPreviewDto());
    }
}
