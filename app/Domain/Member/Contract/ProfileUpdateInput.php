<?php

declare(strict_types=1);

namespace App\Domain\Member\Contract;

/**
 * 小程序会员资料修改输入契约.
 */
interface ProfileUpdateInput
{
    public function getAvatarUrl(): ?string;

    public function getNickname(): ?string;

    public function getGender(): ?int;

    public function getPhone(): ?string;
}
