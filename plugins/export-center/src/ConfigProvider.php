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

namespace Plugin\ExportCenter;

use Hyperf\AsyncQueue\Driver\RedisDriver;
use Plugin\ExportCenter\Process\ExportConsumerProcess;

class ConfigProvider
{
    public function __invoke()
    {
        return [
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'async_queue' => [
                'export' => [
                    'driver' => RedisDriver::class,
                    'redis' => [
                        'pool' => 'default',
                    ],
                    'channel' => '{export-queue}',
                    'timeout' => 2,
                    'retry_seconds' => 10,
                    'handle_timeout' => 600,
                    'processes' => 1,
                    'concurrent' => [
                        'limit' => 5,
                    ],
                    'max_messages' => 0,
                ],
            ],
            'processes' => [
                ExportConsumerProcess::class,
            ],
        ];
    }
}
