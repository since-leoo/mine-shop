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

namespace App\Interface\Common;

use App\Infrastructure\Exception\System\BusinessException;
use Lcobucci\JWT\Token\RegisteredClaims;
use Lcobucci\JWT\UnencryptedToken;
use Mine\Jwt\Traits\RequestScopedTokenTrait;

/**
 * 在 API 层解析会员身份.
 */
class CurrentMember
{
    use RequestScopedTokenTrait;

    private ?int $cachedId = null;

    public function id(): int
    {
        if ($this->cachedId !== null) {
            return $this->cachedId;
        }

        try {
            $token = $this->requireToken();
            $identifier = (string) $token->claims()->get(RegisteredClaims::ID);

            if (! str_starts_with($identifier, 'member:')) {
                throw new BusinessException(ResultCode::FORBIDDEN, '令牌非法');
            }

            $parts = explode(':', $identifier, 2);
            if (\count($parts) !== 2 || ! is_numeric($parts[1])) {
                throw new BusinessException(ResultCode::FORBIDDEN, '令牌非法');
            }

            return $this->cachedId = (int) $parts[1];
        } catch (BusinessException $e) {
            return 0;
        }
    }

    private function requireToken(): UnencryptedToken
    {
        $token = $this->getToken();
        if ($token === null) {
            throw new BusinessException(ResultCode::UNAUTHORIZED, '请先登录');
        }
        return $token;
    }
}
