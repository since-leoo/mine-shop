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
use Hyperf\Validation\Validator;

final class CartItemUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'is_selected' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'quantity' => '数量',
            'is_selected' => '选择状态',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('quantity') && ! $this->has('is_selected')) {
                $validator->errors()->add('quantity', '请至少提供数量或选择状态');
            }
        });
    }
}
