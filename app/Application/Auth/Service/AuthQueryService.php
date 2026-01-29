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

namespace App\Application\Auth\Service;

use App\Domain\Auth\Service\AuthService;
use Lcobucci\JWT\UnencryptedToken;

final class AuthQueryService
{
    public function __construct(private readonly AuthService $authService) {}

    public function check(UnencryptedToken $token): void
    {
        $this->authService->check($token);
    }
}
