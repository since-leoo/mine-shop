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

use App\Domain\Auth\ValueObject\TokenPair;
use App\Domain\Permission\Mapper\UserMapper;
use App\Domain\Permission\Repository\UserRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Exception\Auth\JwtInBlackException;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Admin\DTO\PassportLoginDto;
use App\Interface\Common\ResultCode;
use Lcobucci\JWT\Token\RegisteredClaims;
use Lcobucci\JWT\UnencryptedToken;
use Mine\JwtAuth\Event\UserLoginEvent;

/**
 * 认证领域服务，封装登录/登出等核心逻辑.
 */
final class AuthService extends IService
{
    /**
     * 构造函数，初始化用户仓库和令牌服务.
     *
     * @param TokenService $tokenService 令牌服务实例
     */
    public function __construct(
        private readonly UserRepository $repository,
        private readonly TokenService $tokenService,
    ) {}

    /**
     * 用户登录方法，验证用户凭据并生成访问令牌和刷新令牌.
     *
     * @param PassportLoginDto $dto 登录数据传输对象，包含用户名、密码等信息
     * @return TokenPair 返回包含访问令牌和刷新令牌的对象
     * @throws BusinessException 当用户不存在、密码错误或账户被禁用时抛出异常
     */
    public function login(PassportLoginDto $dto): TokenPair
    {
        // 获取用户信息
        $user = $this->repository->findByUnameType($dto->username, $dto->userType);

        if (! $user) {
            throw new BusinessException(ResultCode::NOT_FOUND, trans('auth.password_error'));
        }
        $userEntity = UserMapper::fromModel($user);

        if (! $userEntity->verifyPassword($dto)) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, trans('auth.password_error'));
        }

        if ($userEntity->getStatus()->isDisable()) {
            throw new BusinessException(ResultCode::DISABLED);
        }

        event(new UserLoginEvent($user, $dto->ip, $dto->os, $dto->browser));

        return $this->buildTokenPair((string) $user->id);
    }

    /**
     * 用户登出方法，将当前令牌加入黑名单.
     *
     * @param UnencryptedToken $token 当前用户的JWT令牌
     */
    public function logout(UnencryptedToken $token): void
    {
        $this->tokenService->addBlackList($token);
    }

    /**
     * 刷新令牌方法，将旧令牌加入黑名单并生成新的访问令牌和刷新令牌.
     *
     * @param UnencryptedToken $token 当前用户的JWT令牌
     * @return TokenPair 返回包含新访问令牌和刷新令牌的对象
     */
    public function refresh(UnencryptedToken $token): TokenPair
    {
        $this->tokenService->addBlackList($token);
        $userId = (string) $token->claims()->get(RegisteredClaims::ID);
        return $this->buildTokenPair($userId);
    }

    /**
     * 检查令牌是否在黑名单中.
     *
     * @param UnencryptedToken $token 需要检查的JWT令牌
     * @throws JwtInBlackException 当令牌在黑名单中时抛出异常
     */
    public function check(UnencryptedToken $token): void
    {
        if ($this->tokenService->isBlacklisted($token)) {
            throw new JwtInBlackException();
        }
    }

    /**
     * 构建令牌对（访问令牌和刷新令牌）.
     *
     * @param string $userId 用户唯一标识符
     * @return TokenPair 返回包含访问令牌和刷新令牌的对象
     */
    private function buildTokenPair(string $userId): TokenPair
    {
        return (new TokenPair())
            ->setAccessToken($this->tokenService->buildAccessToken($userId))
            ->setRefreshToken($this->tokenService->buildRefreshToken($userId))
            ->setExpireAt($this->tokenService->getTtl());
    }
}
