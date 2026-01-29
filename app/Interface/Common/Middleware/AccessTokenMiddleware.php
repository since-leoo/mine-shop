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

namespace App\Interface\Common\Middleware;

use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;
use Mine\Jwt\JwtInterface;
use Mine\JwtAuth\Middleware\AbstractTokenMiddleware;
use Psr\Http\Message\ServerRequestInterface;

final class AccessTokenMiddleware extends AbstractTokenMiddleware
{
    public function getJwt(): JwtInterface
    {
        return $this->jwtFactory->get();
    }

    protected function getToken(ServerRequestInterface $request): string
    {
        $token = parent::getToken($request);
        if ($token === '') {
            throw new BusinessException(ResultCode::UNAUTHORIZED, trans('jwt.unauthorized'));
        }
        return $token;
    }
}
