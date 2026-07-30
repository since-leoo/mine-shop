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

use Hyperf\Context\ResponseContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CorsMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() === 'OPTIONS') {
            return $this->setHeader(ResponseContext::get(), $request);
        }

        return $this->setHeader($handler->handle($request), $request);
    }

    private function setHeader(ResponseInterface $response, ServerRequestInterface $request): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin');
        $allowOrigin = $origin !== '' ? $origin : '*';
        $vary = $origin !== '' ? 'Origin' : '';

        // @phpstan-ignore-next-line
        $response = $response->setHeader('Access-Control-Allow-Origin', $allowOrigin)
            ->setHeader('Access-Control-Allow-Credentials', 'true')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, DELETE, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'DNT,Keep-Alive,User-Agent,Cache-Control,Content-Type,Authorization,Accept-Language,X-Body-Sha256,X-Client-Id,X-Nonce,X-Signature,X-Timestamp')
            ->setHeader('Access-Control-Expose-Headers', 'Request-Id');

        if ($vary !== '') {
            $response = $response->setHeader('Vary', $vary);
        }

        return $response;
    }
}
