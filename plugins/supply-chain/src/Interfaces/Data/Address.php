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

namespace Plugin\SupplyChain\Interfaces\Data;

use App\Infrastructure\Error\SupplyChainError;

final readonly class Address
{
    public function __construct(
        public string $consignee,
        public string $phone,
        public string $province,
        public string $city,
        public string $area,
        public string $description,
        public string $street = '其他',
    ) {
        foreach ([$consignee, $phone, $province, $city, $area, $description] as $value) {
            if (trim($value) === '') {
                throw SupplyChainError::SHIPPING_ADDRESS_INCOMPLETE->invalid();
            }
        }
    }
}
