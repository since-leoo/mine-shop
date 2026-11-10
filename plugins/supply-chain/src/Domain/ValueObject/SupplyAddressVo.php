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

namespace Plugin\SupplyChain\Domain\ValueObject;

use Plugin\SupplyChain\Interfaces\Data\Address;

final class SupplyAddressVo
{
    public static function fromArray(array $data): Address
    {
        return new Address((string) ($data['name'] ?? $data['consignee'] ?? ''), (string) ($data['phone'] ?? ''), (string) ($data['province'] ?? ''), (string) ($data['city'] ?? ''), (string) ($data['district'] ?? $data['area'] ?? ''), (string) ($data['detail'] ?? $data['description'] ?? ''), (string) ($data['street'] ?? '其他'));
    }
}
