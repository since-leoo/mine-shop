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
     * @return array<string, mixed>
     */
    private function ruleset(): array
    {
        return [
            'name' => ['required_without:name', 'nullable', 'string', 'max:50'],
            'phone' => ['required_without:phone', 'nullable', 'string', 'max:20'],
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
}
