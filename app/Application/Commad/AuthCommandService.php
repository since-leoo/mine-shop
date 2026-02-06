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

use App\Domain\Auth\Contract\LoginInput;
use App\Domain\Auth\Service\AuthService;
use App\Domain\Auth\ValueObject\TokenPair;
use Lcobucci\JWT\UnencryptedToken;

/**
 * 提供认证相关的命令服务，封装了登录、登出和刷新令牌等操作。
 */
final class AuthCommandService
{
    /**
     * 构造函数，注入认证服务实例。
     *
     * @param AuthService $authService 认证服务实例，用于处理具体的认证逻辑
     */
    public function __construct(private readonly AuthService $authService) {}

    /**
     * 用户登录方法，通过传递的登录数据进行身份验证并返回令牌对。
     *
     * @param LoginInput $dto 登录数据传输对象，包含用户凭证信息
     * @return TokenPair 返回包含访问令牌和刷新令牌的令牌对
     */
    public function login(LoginInput $dto): TokenPair
    {
        return $this->authService->login($dto);
    }

    /**
     * 用户登出方法，使指定的令牌失效。
     *
     * @param UnencryptedToken $token 需要注销的 JWT 令牌
     */
    public function logout(UnencryptedToken $token): void
    {
        $this->authService->logout($token);
    }

    /**
     * 刷新令牌方法，使用旧令牌生成新的令牌对。
     *
     * @param UnencryptedToken $token 用于刷新的旧 JWT 令牌
     * @return TokenPair 返回新生成的访问令牌和刷新令牌的令牌对
     */
    public function refresh(UnencryptedToken $token): TokenPair
    {
        return $this->authService->refresh($token);
    }
}
