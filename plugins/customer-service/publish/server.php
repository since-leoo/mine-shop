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
use Hyperf\Server\Event;
use Hyperf\Server\ServerInterface;
use Plugin\CustomerService\Socket\CustomerServiceSocketHandler;

return [
    'servers' => [
        [
            'name' => 'customer-service',
            'type' => ServerInterface::SERVER_WEBSOCKET,
            'host' => '0.0.0.0',
            'port' => 9502,
            'sock_type' => \SWOOLE_SOCK_TCP,
            'callbacks' => [
                Event::ON_OPEN => [CustomerServiceSocketHandler::class, 'onOpen'],
                Event::ON_MESSAGE => [CustomerServiceSocketHandler::class, 'onMessage'],
                Event::ON_CLOSE => [CustomerServiceSocketHandler::class, 'onClose'],
            ],
        ],
    ],
];
