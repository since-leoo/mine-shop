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

namespace App\Application\Commad;

use App\Domain\Auth\Service\AuthService;
use App\Domain\Auth\ValueObject\TokenPair;
use App\Interface\Admin\Dto\PassportLoginDto;
use Lcobucci\JWT\UnencryptedToken;

final class AuthCommandService
{
    public function __construct(private readonly AuthService $authService) {}

    public function login(PassportLoginDto $dto): TokenPair
    {
        return $this->authService->login($dto);
    }

    public function logout(UnencryptedToken $token): void
    {
        $this->authService->logout($token);
    }

    public function refresh(UnencryptedToken $token): TokenPair
    {
        return $this->authService->refresh($token);
    }
}
