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

use App\Domain\Auth\Entity\LoginEntity;
use App\Domain\Auth\Service\AuthService;
use App\Domain\Auth\ValueObject\TokenPair;
use Lcobucci\JWT\UnencryptedToken;

final class AuthCommandService
{
    public function __construct(private readonly AuthService $authService) {}

    public function login(LoginEntity $entity): TokenPair
    {
        return $this->authService->login($entity);
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
