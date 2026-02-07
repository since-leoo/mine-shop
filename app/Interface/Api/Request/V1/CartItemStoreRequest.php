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

use App\Domain\Member\Contract\CartItemInput;
use App\Interface\Api\DTO\Cart\CartItemDto;
use App\Interface\Common\Request\BaseRequest;
use Hyperf\DTO\Mapper;

final class CartItemStoreRequest extends BaseRequest
{
    /**
     * 转换为 DTO.
     */
    public function toDto(): CartItemInput
    {
        return Mapper::map($this->validated(), new CartItemDto());
    }

    public function rules(): array
    {
        return [
            'sku_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'is_selected' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'sku_id' => 'SKU',
            'quantity' => '数量',
            'is_selected' => '选择状态',
        ];
    }
}
