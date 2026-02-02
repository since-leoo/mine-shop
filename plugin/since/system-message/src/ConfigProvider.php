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

namespace Plugin\Since\SystemMessage;

use Plugin\Since\SystemMessage\Listener\SendMessageListener;
use Plugin\Since\SystemMessage\Repository\MessageRepository;
use Plugin\Since\SystemMessage\Repository\TemplateRepository;
use Plugin\Since\SystemMessage\Repository\UserPreferenceRepository;
use Plugin\Since\SystemMessage\Service\MessageService;
use Plugin\Since\SystemMessage\Service\NotificationService;
use Plugin\Since\SystemMessage\Service\TemplateService;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            // 依赖注入配置
            'dependencies' => [
                MessageService::class => MessageService::class,
                NotificationService::class => NotificationService::class,
                TemplateService::class => TemplateService::class,
                MessageRepository::class => MessageRepository::class,
                TemplateRepository::class => TemplateRepository::class,
                UserPreferenceRepository::class => UserPreferenceRepository::class,
            ],

            // 注解扫描配置
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],

            // 命令行配置
            'commands' => [],

            'listeners' => [
                SendMessageListener::class,
            ],
        ];
    }
}
