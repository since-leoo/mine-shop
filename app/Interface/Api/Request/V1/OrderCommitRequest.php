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

final class OrderCommitRequest extends OrderPreviewRequest
{
    public function rules(): array
    {
        $base = parent::rules();

        return array_merge($base, [
            'total_amount' => ['nullable', 'integer', 'min:0'],
            'user_name' => ['nullable', 'string', 'max:60'],
            'invoice_request' => ['nullable', 'array'],
            'store_info_list.*.remark' => ['nullable', 'string', 'max:200'],
        ]);
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $data = $this->all();
        if (isset($data['totalAmount']) && ! isset($data['total_amount'])) {
            $this->merge(['total_amount' => $data['totalAmount']]);
        }

        if (isset($data['userName']) && ! isset($data['user_name'])) {
            $this->merge(['user_name' => $data['userName']]);
        }

        if (isset($data['invoiceRequest']) && ! isset($data['invoice_request'])) {
            $this->merge(['invoice_request' => $data['invoiceRequest']]);
        }
    }
}
