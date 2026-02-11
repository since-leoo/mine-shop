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
            'addressId' => (string) $address['id'],
            'name' => (string) ($address['name'] ?? ''),
            'phone' => (string) ($address['phone'] ?? ''),
            'phoneNumber' => (string) ($address['phone'] ?? ''),
            'provinceName' => (string) ($address['province'] ?? ''),
            'provinceCode' => (string) ($address['province_code'] ?? ''),
            'cityName' => (string) ($address['city'] ?? ''),
            'cityCode' => (string) ($address['city_code'] ?? ''),
            'districtName' => (string) ($address['district'] ?? ''),
            'districtCode' => (string) ($address['district_code'] ?? ''),
            'detailAddress' => (string) ($address['detail'] ?? ''),
            'address' => (string) ($address['full_address'] ?? ''),
            'isDefault' => ! empty($address['is_default']) ? 1 : 0,
            'addressTag' => (string) ($address['tag'] ?? ''),
            'latitude' => (string) ($address['latitude'] ?? ''),
            'longitude' => (string) ($address['longitude'] ?? ''),
        ];
    }
}
