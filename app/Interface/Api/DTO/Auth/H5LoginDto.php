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

namespace App\Interface\Api\DTO\Auth;

use App\Domain\Member\Contract\H5LoginInput;

final class H5LoginDto implements H5LoginInput
{
    public string $phone = '';

    public string $password = '';

    public ?string $ip = null;

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }
}
