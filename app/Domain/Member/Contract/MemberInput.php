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

use Carbon\Carbon;

/**
 * 会员输入契约接口.
 */
interface MemberInput
{
    public function getId(): int;

    public function getNickname(): ?string;

    public function getAvatar(): ?string;

    public function getGender(): ?string;

    public function getPhone(): ?string;

    public function getBirthday(): ?Carbon;

    public function getCity(): ?string;

    public function getProvince(): ?string;

    public function getDistrict(): ?string;

    public function getStreet(): ?string;

    public function getRegionPath(): ?string;

    public function getCountry(): ?string;

    public function getLevel(): ?string;

    public function getGrowthValue(): ?int;

    public function getStatus(): ?string;

    public function getSource(): ?string;

    public function getRemark(): ?string;

    /**
     * @return int[]
     */
    public function getTagIds(): array;

    public function getOperatorId(): int;
}
