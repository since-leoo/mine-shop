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

namespace App\Interface\Api\Transformer;

final class MemberAddressTransformer
{
    /**
     * @param array<string, mixed> $address
     * @return array<string, mixed>
     */
    public function transform(array $address): array
    {
        return [
            'id' => (int) $address['id'],
            'name' => (string) ($address['name'] ?? ''),
            'phone' => (string) ($address['phone'] ?? ''),
            'province' => (string) ($address['province'] ?? ''),
            'province_code' => (string) ($address['province_code'] ?? ''),
            'city' => (string) ($address['city'] ?? ''),
            'city_code' => (string) ($address['city_code'] ?? ''),
            'district' => (string) ($address['district'] ?? ''),
            'district_code' => (string) ($address['district_code'] ?? ''),
            'detail' => (string) ($address['detail'] ?? ''),
            'full_address' => (string) ($address['full_address'] ?? ''),
            'is_default' => ! empty($address['is_default']),
        ];
    }
}
