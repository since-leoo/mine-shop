<?php

declare(strict_types=1);

namespace App\Interface\Admin\Request\Order;

use App\Domain\Order\Enum\OrderStatus;
use App\Domain\Order\Enum\PaymentStatus;
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
