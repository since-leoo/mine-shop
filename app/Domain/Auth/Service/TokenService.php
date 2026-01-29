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

use Lcobucci\JWT\UnencryptedToken;
use Mine\Jwt\Factory;
use Mine\Jwt\JwtInterface;

/**
 * 封装 JWT 相关的令牌生成与黑名单操作.
 */
final class TokenService
{
    private string $jwt = 'default';

    public function __construct(private readonly Factory $jwtFactory) {}

    public function buildAccessToken(string $userId): string
    {
        return $this->getJwt()->builderAccessToken($userId)->toString();
    }

    public function buildRefreshToken(string $userId): string
    {
        return $this->getJwt()->builderRefreshToken($userId)->toString();
    }

    public function addBlackList(UnencryptedToken $token): void
    {
        $this->getJwt()->addBlackList($token);
    }

    public function isBlacklisted(UnencryptedToken $token): bool
    {
        return $this->getJwt()->hasBlackList($token);
    }

    public function getTtl(): int
    {
        return (int) $this->getJwt()->getConfig('ttl', 0);
    }

    private function getJwt(): JwtInterface
    {
        return $this->jwtFactory->get($this->jwt);
    }
}
