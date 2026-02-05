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

namespace App\Domain\Auth\Service;

use App\Domain\Auth\Entity\LoginEntity;
use App\Domain\Auth\ValueObject\TokenPair;
use App\Domain\Permission\Repository\UserRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Exception\Auth\JwtInBlackException;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;
use Lcobucci\JWT\Token\RegisteredClaims;
use Lcobucci\JWT\UnencryptedToken;
use Mine\JwtAuth\Event\UserLoginEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * 认证领域服务，封装登录/登出等核心逻辑.
 */
final class AuthService extends IService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TokenService $tokenService,
        private readonly EventDispatcherInterface $dispatcher
    ) {}

    public function login(LoginEntity $entity): TokenPair
    {
        $user = $this->userRepository->findByUnameType($entity->getUsername(), $entity->getUserType());

        if (! $user) {
            throw new BusinessException(ResultCode::NOT_FOUND, trans('auth.password_error'));
        }

        if (! $user->verifyPassword($entity->getPassword())) {
            $client = $entity->getClient();
            $this->dispatcher->dispatch(
                new UserLoginEvent($user, $client->getIp(), $client->getOs(), $client->getBrowser(), false)
            );
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, trans('auth.password_error'));
        }

        if ($user->status->isDisable()) {
            throw new BusinessException(ResultCode::DISABLED);
        }

        $client = $entity->getClient();
        $this->dispatcher->dispatch(new UserLoginEvent($user, $client->getIp(), $client->getOs(), $client->getBrowser()));

        return $this->buildTokenPair((string) $user->id);
    }

    public function logout(UnencryptedToken $token): void
    {
        $this->tokenService->addBlackList($token);
    }

    public function refresh(UnencryptedToken $token): TokenPair
    {
        $this->tokenService->addBlackList($token);
        $userId = (string) $token->claims()->get(RegisteredClaims::ID);
        return $this->buildTokenPair($userId);
    }

    public function check(UnencryptedToken $token): void
    {
        if ($this->tokenService->isBlacklisted($token)) {
            throw new JwtInBlackException();
        }
    }

    private function buildTokenPair(string $userId): TokenPair
    {
        return (new TokenPair())
            ->setAccessToken($this->tokenService->buildAccessToken($userId))
            ->setRefreshToken($this->tokenService->buildRefreshToken($userId))
            ->setExpireAt($this->tokenService->getTtl());
    }
}
