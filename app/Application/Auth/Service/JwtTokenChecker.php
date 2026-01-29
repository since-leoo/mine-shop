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

use Lcobucci\JWT\UnencryptedToken;
use Mine\JwtAuth\Interfaces\CheckTokenInterface;

final class JwtTokenChecker implements CheckTokenInterface
{
    public function __construct(private readonly AuthQueryService $queryService) {}

    public function checkJwt(UnencryptedToken $token): void
    {
        $this->queryService->check($token);
    }
}
