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
    'message' => [
        'max_title_length' => 255,
        'max_content_length' => 10000,
        'default_priority' => 1,
        'retention_days' => 90,
    ],
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
    'template' => [
        'variable_pattern' => '/\{\{(\w+)\}\}/',
        'max_name_length' => 100,
    ],
    'email' => [
        'template' => 'default',
    ],
    'sms' => [
        'max_length' => 70,
    ],
    'push' => [
        'max_length' => 100,
    ],
    'queue' => [
        'channel' => 'default',
    ],
];
