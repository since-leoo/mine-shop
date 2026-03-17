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

namespace App\Interface\Api\Request\V1\AfterSale;

use App\Domain\Trade\AfterSale\Contract\AfterSaleApplyInput;
use App\Interface\Api\DTO\AfterSale\AfterSaleApplyDto;
use Hyperf\DTO\Mapper;
use Hyperf\Validation\Request\FormRequest;

final class AfterSaleApplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 售后申请参数校验规则。
     */
    public function rules(): array
    {
        return [
            'order_id' => 'required|integer|min:1',
            'order_item_id' => 'required|integer|min:1',
            'type' => 'required|string|in:refund_only,return_refund,exchange',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'apply_amount' => 'required|integer|min:0',
            'quantity' => 'required|integer|min:1',
            'images' => 'nullable|array|max:9',
            'images.*' => 'string|max:500',
        ];
    }

    public function attributes(): array
    {
        return [
            'order_id' => '订单ID',
            'order_item_id' => '订单商品项ID',
            'type' => '售后类型',
            'reason' => '申请原因',
            'description' => '问题描述',
            'apply_amount' => '申请金额',
            'quantity' => '售后数量',
            'images' => '凭证图片',
        ];
    }

    /**
     * 将请求数据映射为售后申请 DTO。
     */
    public function toDto(int $memberId): AfterSaleApplyInput
    {
        $payload = $this->validated();
        $payload['member_id'] = $memberId;

        return Mapper::map($payload, new AfterSaleApplyDto());
    }
}