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
            'after_sale_no' => '????',
            'order_no' => '???',
            'member_id' => '??ID',
            'type' => '????',
            'status' => '????',
            'approved_refund_amount' => '????',
            'reject_reason' => '????',
            'remark' => '????',
            'logistics_company' => '??????',
            'logistics_no' => '??????',
        ];
    }

    /**
     * ???????? DTO?
     */
    public function toDto(int $id, int $operatorId, string $operatorName): AfterSaleReviewDto
    {
        $payload = $this->validated();
        $payload['id'] = $id;
        $payload['operator_id'] = $operatorId;
        $payload['operator_name'] = $operatorName;

        return Mapper::map($payload, new AfterSaleReviewDto());
    }

    /**
     * ?????????? DTO?
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
     * ???????? DTO?
     */
    public function toReshipDto(int $id, int $operatorId, string $operatorName): AfterSaleReshipDto
    {
        $payload = $this->validated();
        $payload['id'] = $id;
        $payload['operator_id'] = $operatorId;
        $payload['operator_name'] = $operatorName;

        return Mapper::map($payload, new AfterSaleReshipDto());
    }
}
