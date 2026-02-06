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

namespace App\Domain\Auth\Contract;

use App\Domain\Auth\Enum\Type;
use App\Domain\Auth\ValueObject\ClientInfo;

interface LoginInput
{
    public function getUsername(): string;

    public function getPassword(): string;

    public function getUserType(): Type;

    public function getClientInfo(): ClientInfo;
}
