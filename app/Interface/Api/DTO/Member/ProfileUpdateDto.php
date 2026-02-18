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

use App\Domain\Member\Contract\ProfileUpdateInput;

/**
 * 小程序会员资料修改 DTO.
 */
class ProfileUpdateDto implements ProfileUpdateInput
{
    public ?string $avatar_url = null;

    public ?string $nickname = null;

    public ?int $gender = null;

    public ?string $phone = null;

    public function getAvatarUrl(): ?string
    {
        return $this->avatar_url;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function getGender(): ?int
    {
        return $this->gender;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }
}
