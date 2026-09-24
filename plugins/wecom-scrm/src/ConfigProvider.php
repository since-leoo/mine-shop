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

namespace Plugin\WecomScrm;

use Hyperf\AsyncQueue\Driver\DriverFactory;
use Plugin\WecomScrm\Library\Handler\ContactHandler;
use Plugin\WecomScrm\Library\Handler\CustomerHandler;
use Plugin\WecomScrm\Library\Handler\GroupChatHandler;
use Plugin\WecomScrm\Library\Handler\MessageHandler;
use Plugin\WecomScrm\Library\Handler\TagGroupHandler;
use Plugin\WecomScrm\Library\Handler\TagHandler;
use Plugin\WecomScrm\Service\WecomCallbackService;
use Psr\Container\ContainerInterface;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'mall' => [
                'groups' => [
                    'wecom' => Plugin::mallGroup(),
                ],
            ],
            'dependencies' => [
                TagHandler::class => static fn (ContainerInterface $container) => new TagHandler($container),
                TagGroupHandler::class => static fn (ContainerInterface $container) => new TagGroupHandler($container),
                CustomerHandler::class => static fn (ContainerInterface $container) => new CustomerHandler($container),
                GroupChatHandler::class => static fn (ContainerInterface $container) => new GroupChatHandler($container),
                MessageHandler::class => static fn (ContainerInterface $container) => new MessageHandler($container),
                ContactHandler::class => static fn (ContainerInterface $container) => new ContactHandler($container),
                WecomCallbackService::class => static fn (ContainerInterface $container) => new WecomCallbackService($container->get(DriverFactory::class)),
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [__DIR__],
                ],
            ],
        ];
    }
}
