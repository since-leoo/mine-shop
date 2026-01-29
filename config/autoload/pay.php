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
    'default' => [
        'alipay' => [
            'app_id' => env('ALIPAY_APP_ID', ''),
            'app_secret_cert' => env('ALIPAY_APP_SECRET_CERT', ''),
            'app_public_cert_path' => env('ALIPAY_APP_PUBLIC_CERT_PATH', ''),
            'alipay_public_cert_path' => env('ALIPAY_PUBLIC_CERT_PATH', ''),
            'alipay_root_cert_path' => env('ALIPAY_ROOT_CERT_PATH', ''),
            'return_url' => env('ALIPAY_RETURN_URL', ''),
            'notify_url' => env('ALIPAY_NOTIFY_URL', ''),
            'mode' => env('ALIPAY_MODE', 'normal'),
            'log' => [
                'enable' => env('ALIPAY_LOG_ENABLE', true),
                'file' => BASE_PATH . '/runtime/logs/alipay.log',
                'level' => env('ALIPAY_LOG_LEVEL', 'debug'),
            ],
            'http' => [
                'timeout' => 5.0,
                'connect_timeout' => 5.0,
            ],
        ],
        'wechat' => [
            'app_id' => env('WECHAT_PAY_APP_ID', ''),
            'mini_app_id' => env('WECHAT_PAY_MINI_APP_ID', ''),
            'mch_id' => env('WECHAT_PAY_MCH_ID', ''),
            'mch_secret_key' => env('WECHAT_PAY_MCH_SECRET_KEY', ''),
            'mch_secret_cert' => env('WECHAT_PAY_MCH_SECRET_CERT', ''),
            'mch_public_cert_path' => env('WECHAT_PAY_MCH_PUBLIC_CERT_PATH', ''),
            'notify_url' => env('WECHAT_PAY_NOTIFY_URL', ''),
            'log' => [
                'enable' => env('WECHAT_PAY_LOG_ENABLE', true),
                'file' => BASE_PATH . '/runtime/logs/wechat_pay.log',
                'level' => env('WECHAT_PAY_LOG_LEVEL', 'debug'),
            ],
            'http' => [
                'timeout' => 5.0,
                'connect_timeout' => 5.0,
            ],
        ],
    ],
];
