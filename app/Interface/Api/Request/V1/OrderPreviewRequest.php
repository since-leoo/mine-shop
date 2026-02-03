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

use App\Interface\Common\Request\BaseRequest;

class OrderPreviewRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (isset($data['goodsRequestList']) && ! isset($data['goods_request_list'])) {
            $this->merge(['goods_request_list' => $data['goodsRequestList']]);
        }

        if (isset($data['userAddressReq']) && ! isset($data['user_address'])) {
            $this->merge(['user_address' => $data['userAddressReq']]);
        }

        if (isset($data['addressId']) && ! isset($data['address_id'])) {
            $this->merge(['address_id' => $data['addressId']]);
        }

        if (isset($data['couponList']) && ! isset($data['coupon_list'])) {
            $this->merge(['coupon_list' => $data['couponList']]);
        }

        if (isset($data['storeInfoList']) && ! isset($data['store_info_list'])) {
            $this->merge(['store_info_list' => $data['storeInfoList']]);
        }
    }

    public function rules(): array
    {
        return [
            'goods_request_list' => ['required', 'array', 'min:1'],
            'goods_request_list.*.sku_id' => ['required', 'integer', 'min:1'],
            'goods_request_list.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'user_address' => ['nullable', 'array'],
            'address_id' => ['nullable', 'integer', 'min:1'],
            'coupon_list' => ['nullable', 'array'],
            'coupon_list.*.coupon_id' => ['nullable', 'integer', 'min:1'],
            'store_info_list' => ['nullable', 'array'],
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
}
