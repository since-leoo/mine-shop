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

namespace Plugin\CustomerService\Socket;

use Swoole\WebSocket\Server;

final class CustomerServiceSocketResponder
{
    public function __construct(private readonly CustomerServiceSocketProtocol $protocol) {}

    public function send(Server $server, int $fd, string $event, array $payload = [], array $context = []): void
    {
        if ($server->isEstablished($fd)) {
            $server->push($fd, $this->protocol->encode($event, $payload, $context));
        }
    }

    public function error(Server $server, int $fd, string $message): void
    {
        $this->send($server, $fd, 'system:error', ['message' => $message]);
    }
}
