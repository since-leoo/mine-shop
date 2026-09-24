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

namespace Plugin\CustomerService;

use Plugin\CustomerService\Socket\CustomerServiceConnectionTable;
use Psr\Container\ContainerInterface;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                CustomerServiceConnectionTable::class => static fn (ContainerInterface $container) => new CustomerServiceConnectionTable(CustomerServiceConnectionTable::create()),
            ],
            'server' => require \dirname(__DIR__) . '/publish/server.php',
            'annotations' => [
                'scan' => [
                    'paths' => [__DIR__],
                ],
            ],
            'mall' => ['groups' => Plugin::mallGroups()],
        ];
    }
}
