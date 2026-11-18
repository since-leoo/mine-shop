<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Service;

use App\Infrastructure\Error\CustomerServiceError;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Redis\Redis;
use Plugin\CustomerService\Domain\Contract\CustomerServiceAuthorizationInterface;
use Plugin\CustomerService\Domain\Contract\CustomerServiceEventPublisherInterface;
use Plugin\CustomerService\Socket\CustomerServiceConnectionTable;
use Plugin\CustomerService\Socket\CustomerServiceSocketResponder;
use Swoole\WebSocket\Server;

final class CustomerServiceRealtimeService implements CustomerServiceEventPublisherInterface
{
    /** @var array<string, int> 有容量上限的本进程去重记录，抑制 outbox 重试产生的重复推送。 */
    private array $deliveredEvents = [];

    public function __construct(
        private readonly Redis $redis,
        private readonly ConfigInterface $config,
        private readonly CustomerServiceConnectionTable $connections,
        private readonly CustomerServiceSocketResponder $responder,
        private readonly CustomerServiceAuthorizationInterface $authorization,
    ) {}

    public function channel(): string
    {
        // Redis Pub/Sub 不按 DB 编号隔离，频道需同时带环境前缀和 DB 编号。
        $prefix = (string) $this->config->get('redis.default.options.' . \Redis::OPT_PREFIX, 'mineshop:');
        return $prefix . 'db:' . (int) $this->config->get('redis.default.db', 0) . ':customer-service:events';
    }

    public function publish(string $event, array $payload, ?string $principalType = null, ?int $principalId = null, array $context = []): void
    {
        if (! in_array($principalType, ['member', 'agent'], true) || ($principalType === 'member' && ($principalId ?? 0) <= 0)) {
            throw CustomerServiceError::EVENT_RECIPIENT_REQUIRED->invalid();
        }
        if ($principalId !== null && $principalId <= 0) {
            throw CustomerServiceError::EVENT_RECIPIENT_INVALID->invalid();
        }
        if ($principalType === 'agent' && $principalId === null) {
            if ($event === 'message:created') {
                throw CustomerServiceError::AGENT_BROADCAST_FORBIDDEN->invalid();
            }
            // 广播只通知刷新；列表和私密消息由带权限校验的 HTTP 查询获取。
            $payload = ['refresh' => true];
            $context = array_intersect_key($context, ['event_id' => true]);
        }
        $context['event_id'] ??= bin2hex(random_bytes(16));
        $this->redis->publish($this->channel(), json_encode([
            'event' => $event, 'payload' => $payload, 'context' => $context,
            'principal_type' => $principalType, 'principal_id' => $principalId,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    public function deliver(Server $server, string $encoded): void
    {
        try {
            $message = json_decode($encoded, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return;
        }
        if (! is_array($message) || ! is_string($message['event'] ?? null) || ! is_array($message['payload'] ?? null)) {
            return;
        }
        $type = $message['principal_type'] ?? null;
        $id = isset($message['principal_id']) ? (int) $message['principal_id'] : null;
        if (! in_array($type, ['agent', 'member'], true) || ($type === 'member' && ($id ?? 0) <= 0)) {
            return;
        }
        if (! is_array($message['context'] ?? [])) {
            return;
        }
        if ($type === 'agent' && $id === null) {
            if ($message['event'] === 'message:created') {
                return;
            }
            $message['payload'] = ['refresh' => true];
            $message['context'] = array_intersect_key($message['context'] ?? [], ['event_id' => true]);
        }
        foreach ($this->connections->all() as $fd => $connection) {
            if ($connection['principal_type'] !== $type || ($id !== null && (int) $connection['principal_id'] !== $id)) {
                continue;
            }
            $eventId = $message['context']['event_id'] ?? null;
            // FD 会被复用，必须同时包含连接随机标识，避免新连接被旧去重记录误拦截。
            $deliveryKey = is_string($eventId) ? hash('sha256', $eventId . ':' . $fd . ':' . ($connection['connection_id'] ?? '')) : null;
            if ($deliveryKey !== null && ($this->deliveredEvents[$deliveryKey] ?? 0) > time()) {
                continue;
            }
            if ($type === 'agent') {
                try {
                    $this->authorization->authorizeAgentAction((int) $connection['admin_user_id'], (int) $connection['principal_id'], 'read');
                } catch (\DomainException) {
                    continue;
                }
            }
            $this->responder->send($server, (int) $fd, $message['event'], $message['payload'], $message['context'] ?? []);
            if ($deliveryKey !== null) {
                $this->deliveredEvents[$deliveryKey] = time() + 120;
                if (count($this->deliveredEvents) > 4096) {
                    $this->deliveredEvents = array_slice($this->deliveredEvents, -2048, null, true);
                }
            }
        }
    }
}
