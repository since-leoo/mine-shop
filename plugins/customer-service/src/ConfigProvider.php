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
                \Plugin\CustomerService\Domain\Contract\CustomerServiceAttachmentInterface::class => \Plugin\CustomerService\Infrastructure\Repository\CustomerServiceAttachmentRepository::class,
                \Plugin\CustomerService\Domain\Contract\SupportConfigurationInterface::class => \Plugin\CustomerService\Infrastructure\Repository\DatabaseSupportConfiguration::class,
                \Plugin\CustomerService\Domain\Contract\AiProviderInterface::class => \Plugin\CustomerService\Infrastructure\Ai\HttpCompatibleAiProvider::class,
                \Plugin\CustomerService\Domain\Contract\CustomerServiceKnowledgeInterface::class => \Plugin\CustomerService\Infrastructure\Ai\DatabaseCustomerServiceKnowledge::class,
                \Plugin\CustomerService\Domain\Contract\CustomerServiceEventPublisherInterface::class => \Plugin\CustomerService\Service\CustomerServiceRealtimeService::class,
                \Plugin\CustomerService\Domain\Contract\CustomerServiceTicketStoreInterface::class => \Plugin\CustomerService\Infrastructure\Service\RedisSocketTicketStore::class,
                \Plugin\CustomerService\Domain\Contract\CustomerServiceAuthorizationInterface::class => \Plugin\CustomerService\Infrastructure\Service\CustomerServiceAuthorization::class,
                \Plugin\CustomerService\Domain\Contract\CustomerServicePresenceInterface::class => \Plugin\CustomerService\Infrastructure\Service\RedisCustomerServicePresence::class,
                CustomerServiceConnectionTable::class => static fn (ContainerInterface $container) => new CustomerServiceConnectionTable(CustomerServiceConnectionTable::create()),
            ],
            'processes' => [\Plugin\CustomerService\Process\CustomerServiceEventSubscriber::class],
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
