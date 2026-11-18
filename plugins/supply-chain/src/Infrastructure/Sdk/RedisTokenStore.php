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

namespace Plugin\SupplyChain\Infrastructure\Sdk;

use App\Infrastructure\Error\SupplyChainError;
use Hyperf\Redis\Redis;
use Plugin\SupplyChain\Interfaces\TokenStoreInterface;

final class RedisTokenStore implements TokenStoreInterface
{
    public function __construct(private readonly Redis $redis) {}

    public function get(string $key): ?string
    {
        $value = $this->redis->get($key);
        return \is_string($value) && $value !== '' ? $value : null;
    }

    public function put(string $key, string $token, int $ttl): void
    {
        if (! $this->redis->setex($key, $ttl, $token)) {
            throw SupplyChainError::TOKEN_CACHE_WRITE_FAILED->internal();
        }
    }

    public function forgetIfEquals(string $key, string $token): void
    {
        $this->redis->eval("if redis.call('GET', KEYS[1]) == ARGV[1] then return redis.call('DEL', KEYS[1]) end return 0", [$key, $token], 1);
    }

    public function lock(string $key, string $owner, int $ttl): bool
    {
        return (bool) $this->redis->set($key . ':lock', $owner, ['NX', 'EX' => $ttl]);
    }

    public function unlock(string $key, string $owner): void
    {
        $this->forgetIfEquals($key . ':lock', $owner);
    }
}
