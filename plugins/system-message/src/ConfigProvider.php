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

namespace Plugin\SystemMessage;

use App\Domain\Trade\Shared\Contract\TradeWebhookInterface;
use Plugin\SystemMessage\Infrastructure\Adapter\TradeWebhookAdapter;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                \Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Contract\NotificationTransportInterface::class => \Plugin\SystemMessage\Infrastructure\Adapter\NotificationTransport::class,
            ],
            'plugin_capabilities' => [
                \App\Domain\Infrastructure\Plugin\Contract\SystemNotificationInterface::class => \Plugin\SystemMessage\Infrastructure\Adapter\SystemNotificationAdapter::class,
                TradeWebhookInterface::class => TradeWebhookAdapter::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [__DIR__],
                ],
            ],
            'system_message' => require __DIR__ . '/../publish/system_message.php',
        ];
    }
}
