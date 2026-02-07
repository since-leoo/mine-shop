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

namespace App\Domain\Shipping\Contract;

/**
 * 运费模板输入契约接口.
 */
interface ShippingTemplateInput
{
    public function getId(): int;

    public function getName(): ?string;

    public function getChargeType(): ?string;

    public function getRules(): ?array;

    public function getFreeRules(): ?array;

    public function getIsDefault(): ?bool;

    public function getStatus(): ?string;
}
