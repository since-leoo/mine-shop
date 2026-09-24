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

use App\Domain\Trade\AfterSale\Contract\AfterSaleReturnShipmentInput;
use App\Interface\Api\DTO\AfterSale\AfterSaleReturnShipmentDto;
use Hyperf\DTO\Mapper;
use Hyperf\Validation\Request\FormRequest;

final class AfterSaleReturnShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * æäº¤éè´§ç©æµåæ°æ ¡éªè§åã
     */
    public function rules(): array
    {
        return [
            'logistics_company' => 'required|string|max:100',
            'logistics_no' => 'required|string|max:100',
        ];
    }

    public function attributes(): array
    {
        return [
            'logistics_company' => 'éè´§ç©æµå
¬å¸',
            'logistics_no' => 'éè´§ç©æµåå·',
        ];
    }

    /**
     * å°è¯·æ±æ°æ®æ å°ä¸ºéè´§ç©æµ DTOã
     */
    public function toDto(int $id, int $memberId): AfterSaleReturnShipmentInput
    {
        return Mapper::map([
            ...$this->validated(),
            'id' => $id,
            'member_id' => $memberId,
        ], new AfterSaleReturnShipmentDto());
    }
}
