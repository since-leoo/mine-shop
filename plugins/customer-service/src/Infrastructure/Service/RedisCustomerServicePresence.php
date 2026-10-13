<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Infrastructure\Service;

use Hyperf\Redis\Redis;
use Plugin\CustomerService\Domain\Contract\CustomerServicePresenceInterface;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceAgent;

/** 每个连接持有独立租约；跨标签页和跨节点共享同一坐席的在线判定。 */
final class RedisCustomerServicePresence implements CustomerServicePresenceInterface
{
    private const LEASE_SECONDS = 90;

    private const REFRESH = <<<'LUA'
redis.call('ZREMRANGEBYSCORE', KEYS[1], '-inf', ARGV[1])
redis.call('ZADD', KEYS[1], ARGV[2], ARGV[3])
redis.call('EXPIRE', KEYS[1], ARGV[4])
return 1
LUA;

    private const ONLINE = <<<'LUA'
redis.call('ZREMRANGEBYSCORE', KEYS[1], '-inf', ARGV[1])
return redis.call('ZCARD', KEYS[1])
LUA;

    public function __construct(private readonly Redis $redis) {}

    public function refresh(string $principalType, int $principalId, string $connectionId): void
    {
        $now = time();
        $this->redis->eval(self::REFRESH, [$this->key($principalType, $principalId), $now, $now + self::LEASE_SECONDS, $connectionId, self::LEASE_SECONDS], 1);
        if ($principalType === 'agent') {
            CustomerServiceAgent::query()->whereKey($principalId)->update(['last_heartbeat_at' => date('Y-m-d H:i:s', $now)]);
        }
    }

    public function disconnect(string $principalType, int $principalId, string $connectionId): void
    {
        // 只移除当前连接的租约，其他标签页或节点上的连接仍可能在线。
        $this->redis->zRem($this->key($principalType, $principalId), $connectionId);
    }

    public function isOnline(int $agentId): bool
    {
        return (int) $this->redis->eval(self::ONLINE, [$this->key('agent', $agentId), time()], 1) > 0;
    }

    private function key(string $type, int $id): string
    {
        if (! in_array($type, ['member', 'agent'], true) || $id <= 0) {
            throw new \InvalidArgumentException('无效的客服连接身份');
        }
        return 'customer-service:presence:' . $type . ':' . $id;
    }
}
