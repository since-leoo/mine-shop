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
use Lcobucci\JWT\UnencryptedToken;
use Mine\Jwt\JwtInterface;
use Mine\JwtAuth\Middleware\AbstractTokenMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Swow\Psr7\Message\ServerRequestPlusInterface;

class RefreshTokenMiddleware extends AbstractTokenMiddleware
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->checkToken->checkJwt($this->parserToken($request));
        return $handler->handle(
            value(
                static function (ServerRequestPlusInterface $request, UnencryptedToken $token) {
                    return $request->setAttribute('token', $token);
                },
                $request,
                $this->getJwt()->parserRefreshToken(
                    $this->getToken($request)
                )
            )
        );
    }

    public function getJwt(): JwtInterface
    {
        return $this->jwtFactory->get();
    }

    protected function parserToken(ServerRequestInterface $request): UnencryptedToken
    {
        return $this->getJwt()->parserRefreshToken($this->getToken($request));
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
