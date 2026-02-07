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

namespace App\Interface\Admin\Request\Shipping;

use App\Domain\Shipping\Contract\ShippingTemplateInput;
use App\Interface\Admin\Dto\Shipping\ShippingTemplateDto;
use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\DTO\Mapper;

class ShippingTemplateRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function storeRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'charge_type' => ['required', 'in:weight,quantity,volume'],
            'rules' => ['required', 'array', 'min:1'],
            'rules.*.region_ids' => ['required', 'array', 'min:1'],
            'rules.*.region_ids.*' => ['required', 'integer'],
            'rules.*.first_unit' => ['required', 'integer', 'min:1'],
            'rules.*.first_price' => ['required', 'integer', 'min:0'],
            'rules.*.additional_unit' => ['required', 'integer', 'min:0'],
            'rules.*.additional_price' => ['required', 'integer', 'min:0'],
            'free_rules' => ['nullable', 'array'],
            'free_rules.*.region_ids' => ['required', 'array', 'min:1'],
            'free_rules.*.region_ids.*' => ['required', 'integer'],
            'free_rules.*.free_by_amount' => ['required', 'integer', 'min:0'],
            'free_rules.*.free_by_quantity' => ['required', 'integer', 'min:0'],
            'is_default' => ['nullable', 'boolean'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }

    public function updateRules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'charge_type' => ['sometimes', 'in:weight,quantity,volume'],
            'rules' => ['sometimes', 'array', 'min:1'],
            'rules.*.region_ids' => ['required', 'array', 'min:1'],
            'rules.*.region_ids.*' => ['required', 'integer'],
            'rules.*.first_unit' => ['required', 'integer', 'min:1'],
            'rules.*.first_price' => ['required', 'integer', 'min:0'],
            'rules.*.additional_unit' => ['required', 'integer', 'min:0'],
            'rules.*.additional_price' => ['required', 'integer', 'min:0'],
            'free_rules' => ['nullable', 'array'],
            'free_rules.*.region_ids' => ['required', 'array', 'min:1'],
            'free_rules.*.region_ids.*' => ['required', 'integer'],
            'free_rules.*.free_by_amount' => ['required', 'integer', 'min:0'],
            'free_rules.*.free_by_quantity' => ['required', 'integer', 'min:0'],
            'is_default' => ['nullable', 'boolean'],
            'status' => ['sometimes', 'in:active,inactive'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '模板名称',
            'charge_type' => '计费方式',
            'rules' => '运费规则',
            'rules.*.region_ids' => '地区',
            'rules.*.region_ids.*' => '地区编码',
            'rules.*.first_unit' => '首件/首重/首体积',
            'rules.*.first_price' => '首费',
            'rules.*.additional_unit' => '续件/续重/续体积',
            'rules.*.additional_price' => '续费',
            'free_rules' => '包邮规则',
            'free_rules.*.region_ids' => '包邮地区',
            'free_rules.*.region_ids.*' => '包邮地区编码',
            'free_rules.*.free_by_amount' => '满额包邮门槛',
            'free_rules.*.free_by_quantity' => '满件包邮门槛',
            'is_default' => '是否默认',
            'status' => '状态',
        ];
    }

    /**
     * 转换为 DTO.
     */
    public function toDto(?int $id): ShippingTemplateInput
    {
        $params = $this->validated();
        $params['id'] = $id;

        return Mapper::map($params, new ShippingTemplateDto());
    }
}
