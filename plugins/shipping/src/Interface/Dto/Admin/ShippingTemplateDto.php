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

namespace Plugin\Since\Shipping\Interface\Dto\Admin;

use Plugin\Since\Shipping\Domain\Contract\ShippingTemplateInput;

/**
 * 运费模板 DTO.
 */
class ShippingTemplateDto implements ShippingTemplateInput
{
    public ?int $id = null;

    public ?string $name = null;

    public ?string $charge_type = null;

    public ?array $rules = null;

    public ?array $free_rules = null;

    public ?bool $is_default = null;

    public ?string $status = null;

    public function getId(): int
    {
        return $this->id ?? 0;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getChargeType(): ?string
    {
        return $this->charge_type;
    }

    public function getRules(): ?array
    {
        return $this->rules;
    }

    public function getFreeRules(): ?array
    {
        return $this->free_rules;
    }

    public function getIsDefault(): ?bool
    {
        return $this->is_default;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }
}
