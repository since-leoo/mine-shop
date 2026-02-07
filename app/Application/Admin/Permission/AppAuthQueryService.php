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

namespace App\Application\Admin\Permission;

use App\Domain\Auth\Service\DomainAuthService;
use Lcobucci\JWT\UnencryptedToken;

final class AppAuthQueryService
{
    public function __construct(private readonly DomainAuthService $authService) {}

    public function check(UnencryptedToken $token): void
    {
        $this->authService->check($token);
    }
}
