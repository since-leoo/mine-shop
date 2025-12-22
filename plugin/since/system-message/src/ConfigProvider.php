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

use Plugin\Since\SystemMessage\Middleware\MessagePermissionMiddleware;
use Plugin\Since\SystemMessage\Repository\MessageRepository;
use Plugin\Since\SystemMessage\Repository\TemplateRepository;
use Plugin\Since\SystemMessage\Repository\UserPreferenceRepository;
use Plugin\Since\SystemMessage\Service\MessageService;
use Plugin\Since\SystemMessage\Service\NotificationService;
use Plugin\Since\SystemMessage\Service\SocketIOService;
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
                SocketIOService::class => SocketIOService::class,
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
            'commands' => [
                // MessageSendCommand::class,
                // MessageCleanupCommand::class,
                // SocketIOServerCommand::class,
            ],
            
            // 中间件配置
            'middlewares' => [
                'http' => [
                    MessagePermissionMiddleware::class,
                ],
            ],
            
            // 插件特定配置
            'system_message' => [
                // 消息配置
                'message' => [
                    'max_title_length' => 255,
                    'max_content_length' => 10000,
                    'default_priority' => 1,
                    'retention_days' => 90, // 消息保留天数
                ],
                
                // 通知配置
                'notification' => [
                    'channels' => [
                        'socketio' => [
                            'enabled' => true,
                            'timeout' => 30,
                        ],
                        'websocket' => [
                            'enabled' => true,
                            'timeout' => 30,
                        ],
                        'email' => [
                            'enabled' => true,
                            'queue' => true,
                        ],
                        'sms' => [
                            'enabled' => false,
                            'provider' => 'aliyun',
                        ],
                    ],
                    'retry' => [
                        'max_attempts' => 3,
                        'delay' => 60, // 秒
                    ],
                    'default_channels' => [
                        'socketio' => true,
                        'websocket' => true,
                        'email' => false,
                        'sms' => false,
                        'push' => false,
                    ],
                    'default_types' => [
                        'system' => true,
                        'announcement' => true,
                        'alert' => true,
                        'reminder' => true,
                        'marketing' => false,
                    ],
                ],
                
                // 模板配置
                'template' => [
                    'variable_pattern' => '/\{\{(\w+)\}\}/',
                    'max_variables' => 50,
                    'supported_formats' => ['text', 'html', 'markdown'],
                ],
                
                // 用户偏好配置
                'preferences' => [
                    'defaults' => [
                        'system' => ['socketio', 'websocket'],
                        'announcement' => ['socketio', 'websocket', 'email'],
                        'alert' => ['socketio', 'websocket', 'email'],
                        'reminder' => ['socketio', 'websocket'],
                    ],
                ],
                
                // 安全配置
                'security' => [
                    'encrypt_sensitive' => true,
                    'log_exclude_fields' => ['content', 'template_variables'],
                    'rate_limit' => [
                        'send_per_minute' => 60,
                        'create_per_minute' => 30,
                    ],
                ],
            ],
        ];
    }
}