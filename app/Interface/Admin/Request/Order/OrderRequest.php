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

use App\Domain\Trade\Order\Enum\OrderStatus;
use App\Domain\Trade\Order\Enum\PaymentStatus;
use App\Interface\Admin\Dto\Order\OrderCancelDto;
use App\Interface\Admin\Dto\Order\OrderShipDto;
use App\Interface\Common\Request\BaseRequest;
use Hyperf\Validation\Rule;

final class OrderRequest extends BaseRequest
{
    public function listRules(): array
    {
        return $this->filters();
    }

    public function statsRules(): array
    {
        return $this->dateFilters();
    }

    public function showRules(): array
    {
        return [];
    }

    public function shipRules(): array
    {
        return [
            'shipping_company' => ['required', 'string', 'max:100'],
            'shipping_no' => ['required', 'string', 'max:100'],
            'remark' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function cancelRules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function exportRules(): array
    {
        return $this->filters();
    }

    public function toShipDto(int $orderId, int $operatorId, string $operatorName): OrderShipDto
    {
        $params = $this->validated();
        $dto = new OrderShipDto();
        $dto->order_id = $orderId;
        $dto->operator_id = $operatorId;
        $dto->operator_name = $operatorName;
        $dto->packages = [
            [
                'shipping_company' => (string) ($params['shipping_company'] ?? ''),
                'shipping_no' => (string) ($params['shipping_no'] ?? ''),
                'remark' => (string) ($params['remark'] ?? ''),
                'quantity' => (int) ($params['quantity'] ?? 0),
                'weight' => (float) ($params['weight'] ?? 0),
            ],
        ];
        return $dto;
    }

    public function toCancelDto(int $orderId, int $operatorId, string $operatorName): OrderCancelDto
    {
        $params = $this->validated();
        $dto = new OrderCancelDto();
        $dto->order_id = $orderId;
        $dto->reason = (string) ($params['reason'] ?? '');
        $dto->operator_id = $operatorId;
        $dto->operator_name = $operatorName;
        return $dto;
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(): array
    {
        return [
            'order_no' => ['nullable', 'string', 'max:64'],
            'pay_no' => ['nullable', 'string', 'max:64'],
            'member_id' => ['nullable', 'integer', 'min:1'],
            'member_phone' => ['nullable', 'string', 'max:20'],
            'product_name' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(OrderStatus::values())],
            'pay_status' => ['nullable', Rule::in(PaymentStatus::values())],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dateFilters(): array
    {
        return [
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ];
    }
}
