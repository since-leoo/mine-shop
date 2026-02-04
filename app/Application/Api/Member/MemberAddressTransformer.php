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

namespace App\Application\Api\Member;

final class MemberAddressTransformer
{
    /**
     * @param array<string, mixed> $address
     * @return array<string, mixed>
     */
    public function transform(array $address): array
    {
        return [
            'id' => (string) $address['id'],
            'addressId' => (string) $address['id'],
            'name' => $address['name'] ?? '',
            'phone' => $address['phone'] ?? '',
            'countryName' => $address['country'] ?? '中国',
            'countryCode' => $address['country_code'] ?? 'chn',
            'provinceName' => $address['province'] ?? '',
            'provinceCode' => $address['province_code'] ?? '',
            'cityName' => $address['city'] ?? '',
            'cityCode' => $address['city_code'] ?? '',
            'districtName' => $address['district'] ?? '',
            'districtCode' => $address['district_code'] ?? '',
            'detailAddress' => $address['detail'] ?? '',
            'fullAddress' => $address['full_address'] ?? '',
            'isDefault' => ! empty($address['is_default']) ? 1 : 0,
            'addressTag' => $address['tag'] ?? '',
            'latitude' => $address['latitude'] ?? null,
            'longitude' => $address['longitude'] ?? null,
        ];
    }
}
