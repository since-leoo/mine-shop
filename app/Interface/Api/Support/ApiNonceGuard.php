<?php

declare(strict_types=1);

namespace App\Interface\Api\Support;

use App\Infrastructure\Interface\InterfaceCache;

final class ApiNonceGuard
{
    public function __construct(private readonly InterfaceCache $cache) {}

    public function consume(string $clientId, string $nonce, int $ttl): bool
    {
        $this->cache->setPrefix('api:signature:nonce');

        return $this->cache->set(
            $clientId . ':' . $nonce,
            '1',
            ['NX', 'EX' => $ttl]
        );
    }
}
