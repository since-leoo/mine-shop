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
use Plugin\SupplyChain\Interfaces\RequestLimiterInterface;

final class RedisRequestLimiter implements RequestLimiterInterface
{
    public function __construct(private readonly Redis $redis, private readonly int $concurrency = 4, private readonly int $requestsPerSecond = 5) {}

    public function acquire(int $connectionId, int $leaseSeconds): string
    {
        $lease = bin2hex(random_bytes(16));
        $key = 'supply:limit:{' . $connectionId . '}';
        $script = <<<'LUA'
            local now = redis.call('TIME')
            local milliseconds = now[1] * 1000 + math.floor(now[2] / 1000)
            redis.call('ZREMRANGEBYSCORE', KEYS[1], '-inf', milliseconds)
            if redis.call('ZCARD', KEYS[1]) >= tonumber(ARGV[2]) then return 0 end
            local count = tonumber(redis.call('GET', KEYS[2]) or '0')
            if count >= tonumber(ARGV[3]) then return 0 end
            redis.call('INCR', KEYS[2])
            if count == 0 then redis.call('PEXPIRE', KEYS[2], 1000) end
            redis.call('ZADD', KEYS[1], milliseconds + tonumber(ARGV[4]) * 1000, ARGV[1])
            redis.call('EXPIRE', KEYS[1], tonumber(ARGV[4]) + 1)
            return 1
            LUA;
        $accepted = $this->redis->eval($script, [$key . ':active', $key . ':rate', $lease,
            max(1, $this->concurrency), max(1, $this->requestsPerSecond), $leaseSeconds], 2);
        if ((int) $accepted !== 1) {
            throw new \RuntimeException(SupplyChainError::REQUEST_RATE_LIMITED->message(), 429);
        }
        return $lease;
    }

    public function release(int $connectionId, string $lease): void
    {
        $this->redis->zRem('supply:limit:{' . $connectionId . '}:active', $lease);
    }
}
