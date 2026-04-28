<?php

declare(strict_types=1);

namespace App\Interface\Api\Middleware;

use App\Interface\Api\Support\ApiSignatureConfig;
use App\Interface\Api\Support\ApiSignatureVerifier;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ApiSignatureMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ApiSignatureConfig $config,
        private readonly ApiSignatureVerifier $verifier,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! $this->config->enabled() || strtoupper($request->getMethod()) === 'OPTIONS') {
            return $handler->handle($request);
        }

        $path = $request->getUri()->getPath();
        if (! str_starts_with($path, '/api/v1/')) {
            return $handler->handle($request);
        }

        $this->verifier->verify($request);

        return $handler->handle($request);
    }
}
