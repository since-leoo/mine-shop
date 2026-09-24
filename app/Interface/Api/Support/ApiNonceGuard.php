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
