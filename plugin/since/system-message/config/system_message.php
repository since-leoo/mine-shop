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

return [
    // 消息配置
    'message' => [
        'max_title_length' => 255,
        'max_content_length' => 10000,
        'default_priority' => 1,
        'retention_days' => 90, // 消息保留天数
        'batch_size' => 100, // 批量处理大小
    ],
    
    // 通知配置
    'notification' => [
        'channels' => [
            'websocket' => [
                'enabled' => true,
                'timeout' => 30,
                'retry_times' => 3,
            ],
            'email' => [
                'enabled' => true,
                'queue' => true,
                'template' => 'system_message_email',
            ],
            'sms' => [
                'enabled' => false,
                'provider' => 'aliyun',
                'template_code' => 'SMS_123456789',
            ],
            // 为未来的小程序推送预留配置
            'miniapp' => [
                'enabled' => false,
                'wechat' => [
                    'app_id' => '',
                    'app_secret' => '',
                    'template_id' => '',
                ],
                'alipay' => [
                    'app_id' => '',
                    'private_key' => '',
                    'template_id' => '',
                ],
            ],
        ],
        'retry' => [
            'max_attempts' => 3,
            'delay' => 60, // 秒
            'backoff' => 'exponential', // linear, exponential
        ],
    ],
    
    // 模板配置
    'template' => [
        'variable_pattern' => '/\{\{(\w+)\}\}/',
        'max_variables' => 50,
        'supported_formats' => ['text', 'html', 'markdown'],
        'cache_ttl' => 3600, // 模板缓存时间（秒）
    ],
    
    // 用户偏好配置
    'preferences' => [
        'defaults' => [
            'system' => ['websocket'],
            'announcement' => ['websocket', 'email'],
            'alert' => ['websocket', 'email'],
            'reminder' => ['websocket'],
        ],
        'do_not_disturb' => [
            'default_start' => '22:00',
            'default_end' => '08:00',
        ],
    ],
    
    // 安全配置
    'security' => [
        'encrypt_sensitive' => true,
        'encryption_key' => env('SYSTEM_MESSAGE_ENCRYPTION_KEY', ''),
        'log_exclude_fields' => ['content', 'template_variables'],
        'rate_limit' => [
            'send_per_minute' => 60,
            'create_per_minute' => 30,
            'api_per_minute' => 120,
        ],
        'content_filter' => [
            'enabled' => true,
            'max_length' => 10000,
            'forbidden_words' => [],
        ],
    ],
    
    // 队列配置
    'queue' => [
        'channel' => 'system_message',
        'timeout' => 60,
        'retry_seconds' => [1, 5, 10, 20],
        'max_attempts' => 3,
        'concurrent_limit' => 10,
    ],
    
    // WebSocket 配置
    'websocket' => [
        'enabled' => true,
        'port' => 9902,
        'path' => '/system-message',
        'heartbeat' => 30,
        'max_connections' => 1000,
    ],
    
    // 缓存配置
    'cache' => [
        'prefix' => 'system_message:',
        'ttl' => [
            'message' => 3600,
            'template' => 7200,
            'user_preferences' => 1800,
            'statistics' => 300,
        ],
    ],
    
    // 日志配置
    'logging' => [
        'enabled' => true,
        'level' => 'info',
        'channels' => ['daily'],
        'context' => [
            'include_user_id' => true,
            'include_ip' => true,
            'exclude_sensitive' => true,
        ],
    ],
    
    // 统计配置
    'statistics' => [
        'enabled' => true,
        'retention_days' => 30,
        'metrics' => [
            'message_sent',
            'message_read',
            'delivery_success',
            'delivery_failed',
            'template_used',
        ],
    ],
    
    // 性能配置
    'performance' => [
        'pagination' => [
            'default_size' => 20,
            'max_size' => 100,
        ],
        'search' => [
            'max_results' => 1000,
            'timeout' => 5,
        ],
        'batch_operations' => [
            'max_batch_size' => 1000,
            'chunk_size' => 100,
        ],
    ],
];