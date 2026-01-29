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
    'name' => 'Since Mall Plugin',
    'version' => '1.0.0',
    'description' => 'Complete E-commerce Solution',
    'settings' => [
        'currency' => 'CNY',
        'currency_symbol' => '¥',
        'decimal_places' => 2,
        'thousand_separator' => ',',
        'decimal_separator' => '.',
        'product' => [
            'default_status' => 1,
            'auto_approve' => false,
            'image_max_size' => 2048,
            'image_formats' => [
                0 => 'jpg',
                1 => 'jpeg',
                2 => 'png',
                3 => 'gif',
                4 => 'webp',
            ],
        ],
        'order' => [
            'auto_confirm_days' => 7,
            'auto_close_minutes' => 30,
            'allow_cancel_status' => [
                0 => 'pending',
                1 => 'paid',
            ],
        ],
        'member' => [
            'default_level' => 1,
            'point_rate' => 100,
            'register_points' => 100,
        ],
    ],
    'payment' => [
        'methods' => [
            'alipay' => [
                'name' => '支付宝',
                'enabled' => false,
                'config' => [
                ],
            ],
            'wechat' => [
                'name' => '微信支付',
                'enabled' => false,
                'config' => [
                ],
            ],
            'balance' => [
                'name' => '余额支付',
                'enabled' => true,
                'config' => [
                ],
            ],
        ],
    ],
    'shipping' => [
        'methods' => [
            'express' => [
                'name' => '快递配送',
                'enabled' => true,
                'fee' => 10.0,
                'free_amount' => 99.0,
            ],
            'pickup' => [
                'name' => '到店自提',
                'enabled' => true,
                'fee' => 0.0,
            ],
        ],
    ],
];
