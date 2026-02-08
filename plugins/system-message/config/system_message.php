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
        'retention_days' => 90,
    ],

    // 通知配置
    'notification' => [
        'retry' => [
            'max_attempts' => 3,
        ],
        'default_channels' => [
            'database' => true,
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
        'max_name_length' => 100,
    ],

    // 邮件配置
    'email' => [
        'template' => 'default',
    ],

    // 短信配置
    'sms' => [
        'max_length' => 70,
    ],

    // 推送配置
    'push' => [
        'max_length' => 100,
    ],

    // 队列配置
    'queue' => [
        'channel' => 'default',
    ],
];
