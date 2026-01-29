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

namespace App\Domain\Auth\ValueObject;

/**
 * 封装 access/refresh token 及过期时间.
 */
final class TokenPair
{
    private string $accessToken = '';

    private string $refreshToken = '';

    private int $expireAt = 0;

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken = ''): self
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(string $refreshToken = ''): self
    {
        $this->refreshToken = $refreshToken;
        return $this;
    }

    public function getExpireAt(): int
    {
        return $this->expireAt;
    }

    public function setExpireAt(int $expireAt = 0): self
    {
        $this->expireAt = $expireAt;
        return $this;
    }

    /**
     * @return array{access_token:string,refresh_token:string,expire_at:int}
     */
    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'expire_at' => $this->expireAt,
        ];
    }
}
