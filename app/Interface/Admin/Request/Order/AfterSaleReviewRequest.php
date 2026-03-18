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

namespace App\Interface\Admin\Request\Order;

use App\Domain\Trade\AfterSale\Enum\AfterSaleStatus;
use App\Domain\Trade\AfterSale\Enum\AfterSaleType;
use App\Interface\Admin\Dto\Order\AfterSaleActionDto;
use App\Interface\Admin\Dto\Order\AfterSaleReshipDto;
use App\Interface\Admin\Dto\Order\AfterSaleReviewDto;
use App\Interface\Common\Request\BaseRequest;
use Hyperf\DTO\Mapper;
use Hyperf\Validation\Rule;

final class AfterSaleReviewRequest extends BaseRequest
{
    public function listRules(): array
    {
        return [
            'after_sale_no' => ['nullable', 'string', 'max:64'],
            'order_no' => ['nullable', 'string', 'max:64'],
            'member_id' => ['nullable', 'integer', 'min:1'],
            'type' => ['nullable', Rule::in(AfterSaleType::values())],
            'status' => ['nullable', Rule::in(AfterSaleStatus::values())],
        ];
    }

    public function showRules(): array
    {
        return [];
    }

    public function approveRules(): array
    {
        return [
            'approved_refund_amount' => ['nullable', 'integer', 'min:0'],
            'remark' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function rejectRules(): array
    {
        return [
            'reject_reason' => ['required', 'string', 'max:200'],
            'remark' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function receiveRules(): array
    {
        return [];
    }

    public function refundRules(): array
    {
        return [];
    }

    public function reshipRules(): array
    {
        return [
            'logistics_company' => ['required', 'string', 'max:100'],
            'logistics_no' => ['required', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'after_sale_no' => '售后单号',
            'order_no' => '订单号',
            'member_id' => '会员ID',
            'type' => '售后类型',
            'status' => '售后状态',
            'approved_refund_amount' => '审核退款金额',
            'reject_reason' => '驳回原因',
            'remark' => '备注',
            'logistics_company' => '物流公司',
            'logistics_no' => '物流单号',
        ];
    }

    /**
     * 转换审核 DTO?
     */
    public function toDto(int $id, int $operatorId, string $operatorName): AfterSaleReviewDto
    {
        return Mapper::map([
            ...$this->validated(),
            'id' => $id,
            'operator_id' => $operatorId,
            'operator_name' => $operatorName,
        ], new AfterSaleReviewDto());
    }

    /**
     * 转换通用动作 DTO?
     */
    public function toActionDto(int $id, int $operatorId, string $operatorName): AfterSaleActionDto
    {
        return Mapper::map([
            'id' => $id,
            'operator_id' => $operatorId,
            'operator_name' => $operatorName,
        ], new AfterSaleActionDto());
    }

    /**
     * 转换补发 DTO?
     */
    public function toReshipDto(int $id, int $operatorId, string $operatorName): AfterSaleReshipDto
    {
        return Mapper::map([
            ...$this->validated(),
            'id' => $id,
            'operator_id' => $operatorId,
            'operator_name' => $operatorName,
        ], new AfterSaleReshipDto());
    }
}
