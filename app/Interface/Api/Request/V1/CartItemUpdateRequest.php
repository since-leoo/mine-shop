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
use Hyperf\Validation\Validator;

final class CartItemUpdateRequest extends BaseRequest
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
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }

    public function attributes(): array
    {
        return [
            'quantity' => '数量',
        ];
    }

    public function withValidator(Validator $validator): void {}
}
