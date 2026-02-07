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

namespace App\Interface\Api\DTO\Member;

use App\Domain\Member\Contract\MemberAddressInput;

/**
 * 会员收货地址 DTO.
 */
class MemberAddressDto implements MemberAddressInput
{
    public ?string $name = null;

    public ?string $phone = null;

    public ?string $province = null;

    public ?string $province_code = null;

    public ?string $city = null;

    public ?string $city_code = null;

    public ?string $district = null;

    public ?string $district_code = null;

    public ?string $detail = null;

    public ?bool $is_default = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getProvince(): ?string
    {
        return $this->province;
    }

    public function getProvinceCode(): ?string
    {
        return $this->province_code;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getCityCode(): ?string
    {
        return $this->city_code;
    }

    public function getDistrict(): ?string
    {
        return $this->district;
    }

    public function getDistrictCode(): ?string
    {
        return $this->district_code;
    }

    public function getDetail(): ?string
    {
        return $this->detail;
    }

    public function getIsDefault(): ?bool
    {
        return $this->is_default;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'phone' => $this->phone,
            'province' => $this->province,
            'province_code' => $this->province_code,
            'city' => $this->city,
            'city_code' => $this->city_code,
            'district' => $this->district,
            'district_code' => $this->district_code,
            'detail' => $this->detail,
            'is_default' => $this->is_default,
        ], static fn ($value) => $value !== null);
    }
}
