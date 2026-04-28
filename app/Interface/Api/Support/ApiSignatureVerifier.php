<?php

declare(strict_types=1);

namespace App\Interface\Api\Support;

use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;
use Psr\Http\Message\ServerRequestInterface;

final class ApiSignatureVerifier
{
    public function __construct(
        private readonly ApiSignatureConfig $config,
        private readonly ApiNonceGuard $nonceGuard,
    ) {}

    public function verify(ServerRequestInterface $request): void
    {
        $clientId = trim($request->getHeaderLine('X-Client-Id'));
        $timestamp = trim($request->getHeaderLine('X-Timestamp'));
        $nonce = trim($request->getHeaderLine('X-Nonce'));
        $bodySha256 = trim($request->getHeaderLine('X-Body-Sha256'));
        $signature = trim($request->getHeaderLine('X-Signature'));

        if ($clientId === '' || $timestamp === '' || $nonce === '' || $bodySha256 === '' || $signature === '') {
            throw new BusinessException(ResultCode::UNAUTHORIZED, 'Invalid API signature headers.');
        }

        $timestampValue = (int) $timestamp;
        if ($timestampValue <= 0 || abs(time() - $timestampValue) > $this->config->ttl()) {
            throw new BusinessException(ResultCode::UNAUTHORIZED, 'API signature timestamp expired.');
        }

        $secret = $this->config->clientSecret($clientId);
        if ($secret === null) {
            throw new BusinessException(ResultCode::UNAUTHORIZED, 'Unsupported API client.');
        }

        $rawBody = $this->bodyForHash($request);
        $actualBodySha256 = hash('sha256', $rawBody);
        if (! hash_equals($actualBodySha256, $bodySha256)) {
            throw new BusinessException(ResultCode::UNAUTHORIZED, 'API request body hash mismatch.');
        }

        $payload = implode("\n", [
            strtoupper($request->getMethod()),
            $request->getUri()->getPath(),
            $request->getUri()->getQuery(),
            $timestamp,
            $nonce,
            $bodySha256,
            $clientId,
        ]);

        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        if (! hash_equals($expectedSignature, $signature)) {
            throw new BusinessException(ResultCode::UNAUTHORIZED, 'Invalid API signature.');
        }

        if (! $this->nonceGuard->consume($clientId, $nonce, $this->config->ttl())) {
            throw new BusinessException(ResultCode::UNAUTHORIZED, 'Duplicated API request nonce.');
        }
    }

    private function bodyForHash(ServerRequestInterface $request): string
    {
        $contentType = strtolower($request->getHeaderLine('Content-Type'));
        if (str_starts_with($contentType, 'multipart/form-data')) {
            return '';
        }

        return (string) $request->getBody();
    }
}
