<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Process;

use App\Infrastructure\Error\CustomerServiceError;
use Hyperf\Process\AbstractProcess;
use Hyperf\Redis\Redis;
use Hyperf\Server\ServerFactory;
use Plugin\CustomerService\Service\CustomerServiceRealtimeService;
use Swoole\WebSocket\Server;

/** 每个服务实例订阅同一事件频道，再向本实例的授权连接投递。 */
final class CustomerServiceEventSubscriber extends AbstractProcess
{
    public string $name = 'customer-service-events';

    public function handle(): void
    {
        $server = $this->container->get(ServerFactory::class)->getServer()->getServer();
        if (! $server instanceof Server) {
            throw CustomerServiceError::WEBSOCKET_SERVER_REQUIRED->internal();
        }
        $realtime = $this->container->get(CustomerServiceRealtimeService::class);
        $this->container->get(Redis::class)->subscribe([$realtime->channel()], static function ($redis, string $channel, string $message) use ($server, $realtime): void {
            $realtime->deliver($server, $message);
        });
    }
}
