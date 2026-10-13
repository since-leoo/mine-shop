<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Infrastructure\Service;

use Hyperf\Redis\Redis;
use Plugin\CustomerService\Domain\Contract\CustomerServiceTicketStoreInterface;

final class RedisSocketTicketStore implements CustomerServiceTicketStoreInterface
{
    // 读取和删除必须是同一次原子操作，避免不同节点同时用同一票据建立连接。
    private const CONSUME = <<<'LUA'
local value = redis.call('GET', KEYS[1])
if value then redis.call('DEL', KEYS[1]) end
return value
LUA;

    public function __construct(private readonly Redis $redis) {}

    public function put(string $ticket, array $principal, int $expiresIn): void
    {
        if (! $this->redis->set('customer-service:socket-ticket:' . $ticket, json_encode($principal, JSON_THROW_ON_ERROR), ['EX' => $expiresIn])) {
            throw new \RuntimeException('无法创建客服连接凭据');
        }
    }

    public function consume(string $ticket): ?array
    {
        $value = $this->redis->eval(self::CONSUME, ['customer-service:socket-ticket:' . $ticket], 1);
        if (! is_string($value)) {
            return null;
        }
        try {
            $principal = json_decode($value, true, 16, JSON_THROW_ON_ERROR);
            return is_array($principal) ? $principal : null;
        } catch (\JsonException) {
            // 损坏的凭据也已被消费，禁止反复重试绕过一次性校验。
            return null;
        }
    }
}
