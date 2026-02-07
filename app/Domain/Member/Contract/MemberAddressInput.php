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

namespace App\Domain\Member\Contract;

/**
 * 会员收货地址输入契约.
 */
interface MemberAddressInput
{
    public function getName(): ?string;

    public function getPhone(): ?string;

    public function getProvince(): ?string;

    public function getProvinceCode(): ?string;

    public function getCity(): ?string;

    public function getCityCode(): ?string;

    public function getDistrict(): ?string;

    public function getDistrictCode(): ?string;

    public function getDetail(): ?string;

    public function getIsDefault(): ?bool;

    /**
     * 转换为数组（用于简单 CRUD 操作）.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
