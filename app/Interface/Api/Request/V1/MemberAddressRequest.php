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

use App\Domain\Member\Contract\MemberAddressInput;
use App\Interface\Api\DTO\Member\MemberAddressDto;
use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\DTO\Mapper;

final class MemberAddressRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    /**
     * 转换为 DTO.
     */
    public function toDto(): MemberAddressInput
    {
        return Mapper::map($this->validated(), new MemberAddressDto());
    }

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
            'name' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:20'],
            'province' => ['required', 'string', 'max:50'],
            'province_code' => ['nullable', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:50'],
            'city_code' => ['nullable', 'string', 'max:20'],
            'district' => ['required', 'string', 'max:50'],
            'district_code' => ['nullable', 'string', 'max:20'],
            'detail' => ['required', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
