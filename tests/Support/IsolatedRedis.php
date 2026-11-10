<?php

declare(strict_types=1);

namespace HyperfTests\Support;

use Hyperf\Redis\Redis as HyperfRedis;

/** Uses a private prefix on an explicitly selected disposable Unix-socket server. */
trait IsolatedRedis
{
    private ?\Redis $nativeRedis = null;
    private string $redisPrefix = '';

    private function openRedis(): \Redis
    {
        $socket = getenv('MINE_SHOP_TEST_REDIS_SOCKET');
        if (! is_string($socket) || $socket === '') {
            self::markTestSkipped('Set MINE_SHOP_TEST_REDIS_SOCKET to an isolated local test server.');
        }
        if (! str_starts_with($socket, '/tmp/mine-shop-tests.') || ! str_ends_with($socket, '/redis.sock')) {
            throw new \RuntimeException('Tests require a disposable /tmp/mine-shop-tests.*/redis.sock server.');
        }
        $this->redisPrefix = $this->redisPrefix ?: 'codex:test:' . bin2hex(random_bytes(10)) . ':';
        $redis = new \Redis();
        $redis->connect($socket);
        $redis->setOption(\Redis::OPT_PREFIX, $this->redisPrefix);
        return $redis;
    }

    private function redisBridge(\Redis $redis): HyperfRedis
    {
        $bridge = \Mockery::mock(HyperfRedis::class);
        foreach (['set', 'eval', 'zRem', 'publish'] as $method) {
            $bridge->shouldReceive($method)->andReturnUsing(static fn (...$args) => $redis->{$method}(...$args));
        }
        return $bridge;
    }

    private function cleanRedis(): void
    {
        if ($this->nativeRedis !== null) {
            $this->nativeRedis->setOption(\Redis::OPT_PREFIX, '');
            $keys = $this->nativeRedis->keys($this->redisPrefix . '*');
            if ($keys !== []) { $this->nativeRedis->del($keys); }
            $this->nativeRedis->close();
        }
        \Mockery::close();
    }
}
