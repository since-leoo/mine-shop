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

use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;

final class MemberAddressRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    /**
     * @return array<string, mixed>
     */
    public function storeRules(): array
    {
        return $this->ruleset();
    }

    /**
     * @return array<string, mixed>
     */
    public function updateRules(): array
    {
        return $this->ruleset();
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    public function processStoreData(array $validated): array
    {
        return $this->normalize($validated);
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    public function processUpdateData(array $validated): array
    {
        return $this->normalize($validated);
    }

    /**
     * @return array<string, mixed>
     */
    private function ruleset(): array
    {
        return [
            'receiver_name' => ['required_without:name', 'nullable', 'string', 'max:50'],
            'name' => ['required_without:receiver_name', 'nullable', 'string', 'max:50'],
            'receiver_phone' => ['required_without:phone', 'nullable', 'string', 'max:20'],
            'phone' => ['required_without:receiver_phone', 'nullable', 'string', 'max:20'],
            'province' => ['required_without:provinceName', 'nullable', 'string', 'max:50'],
            'provinceName' => ['required_without:province', 'nullable', 'string', 'max:50'],
            'provinceCode' => ['nullable', 'string', 'max:20'],
            'province_code' => ['nullable', 'string', 'max:20'],
            'city' => ['required_without:cityName', 'nullable', 'string', 'max:50'],
            'cityName' => ['required_without:city', 'nullable', 'string', 'max:50'],
            'cityCode' => ['nullable', 'string', 'max:20'],
            'city_code' => ['nullable', 'string', 'max:20'],
            'district' => ['required_without:districtName', 'nullable', 'string', 'max:50'],
            'districtName' => ['required_without:district', 'nullable', 'string', 'max:50'],
            'districtCode' => ['nullable', 'string', 'max:20'],
            'district_code' => ['nullable', 'string', 'max:20'],
            'detail' => ['required_without:detailAddress', 'nullable', 'string', 'max:200'],
            'detailAddress' => ['required_without:detail', 'nullable', 'string', 'max:200'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalize(array $payload): array
    {
        return [
            'receiver_name' => $payload['receiver_name'] ?? $payload['name'] ?? '',
            'receiver_phone' => $payload['receiver_phone'] ?? $payload['phone'] ?? '',
            'province' => $payload['province'] ?? $payload['provinceName'] ?? '',
            'province_code' => $payload['province_code'] ?? $payload['provinceCode'] ?? null,
            'city' => $payload['city'] ?? $payload['cityName'] ?? '',
            'city_code' => $payload['city_code'] ?? $payload['cityCode'] ?? null,
            'district' => $payload['district'] ?? $payload['districtName'] ?? '',
            'district_code' => $payload['district_code'] ?? $payload['districtCode'] ?? null,
            'detail' => $payload['detail'] ?? $payload['detailAddress'] ?? '',
            'is_default' => (bool) ($payload['is_default'] ?? $payload['isDefault'] ?? false),
        ];
    }
}
