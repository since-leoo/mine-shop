<?php

declare(strict_types=1);

use Hyperf\Server\Event;
use Hyperf\Server\Server;
use Hyperf\Server\ServerInterface;
use Plugin\CustomerService\Socket\CustomerServiceSocketHandler;

return [
    'servers' => [
        [
            'name' => 'customer-service',
            'type' => ServerInterface::SERVER_WEBSOCKET,
            'host' => '0.0.0.0',
            'port' => 9502,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                Event::ON_OPEN => [CustomerServiceSocketHandler::class, 'onOpen'],
                Event::ON_MESSAGE => [CustomerServiceSocketHandler::class, 'onMessage'],
                Event::ON_CLOSE => [CustomerServiceSocketHandler::class, 'onClose'],
            ],
        ],
    ],
];
