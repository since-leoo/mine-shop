<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Service;

use App\Infrastructure\Abstract\ICache;
use Plugin\CustomerService\Socket\CustomerServiceConnectionTable;
use Plugin\CustomerService\Socket\CustomerServiceSocketResponder;
use Swoole\WebSocket\Server;

final class CustomerServiceRealtimeService
{
    private ICache $cache;

    public function __construct(
        ICache $cache,
        private readonly CustomerServiceConnectionTable $connections,
        private readonly CustomerServiceSocketResponder $responder,
    ) {
        $this->cache = clone $cache;
        $this->cache->setPrefix('customer-service:presence');
    }

    public function online(string $principalType, int $principalId): void
    {
        $this->cache->set($principalType . ':' . $principalId, (string) time(), ['EX' => 90]);
        $this->publish('presence:online', ['principal_type' => $principalType, 'principal_id' => $principalId]);
    }

    public function offline(string $principalType, int $principalId): void
    {
        $this->cache->delete($principalType . ':' . $principalId);
        $this->publish('presence:offline', ['principal_type' => $principalType, 'principal_id' => $principalId]);
    }

    public function broadcast(Server $server, string $event, array $payload, ?string $principalType = null, ?int $principalId = null): void
    {
        foreach ($this->connections->all() as $fd => $connection) {
            if ($principalType !== null && ($connection['principal_type'] ?? '') !== $principalType) {
                continue;
            }
            if ($principalId !== null && (int) ($connection['principal_id'] ?? 0) !== $principalId) {
                continue;
            }
            $this->responder->send($server, (int) $fd, $event, $payload);
        }
        $this->publish($event, $payload);
    }

    private function publish(string $event, array $payload): void
    {
        $this->cache->publish('customer-service:events', json_encode(['event' => $event, 'payload' => $payload], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
}
