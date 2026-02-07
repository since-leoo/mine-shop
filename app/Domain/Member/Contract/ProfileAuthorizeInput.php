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
 * 小程序授权头像昵称输入契约.
 */
interface ProfileAuthorizeInput
{
    public function getAvatarUrl(): ?string;

    public function getNickname(): ?string;

    public function getGender(): ?int;
}
