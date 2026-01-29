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
    // appId
    'app_id' => env('WECHAT_APP_ID', ''),
    // appSecret
    'secret' => env('WECHAT_SECRET', ''),
    // Token
    'token' => env('WECHAT_TOKEN', ''),
    // EncodingAESKey(安全模式下，必填)
    'aes_key' => env('WECHAT_AES_KEY', ''),
    /*
     * 是否使用 Stable Access Token
     * 默认 false
     * https://developers.weixin.qq.com/miniprogram/dev/OpenApiDoc/mp-access-token/getStableAccessToken.html
     * true 使用 false 不使用
     */
    'use_stable_access_token' => true,
    /*
     * OAuth 配置
     *
     * scopes：公众平台（snsapi_userinfo / snsapi_base），开放平台：snsapi_login
     * redirect_url：OAuth授权完成后的回调页地址
     */
    'oauth' => [
        'scopes' => ['snsapi_userinfo'],
        'redirect_url' => env('WECHAT_OAUTH_CALLBACK_URL', '/examples/oauth_callback.php'),
    ],
    // 下面为可选项
    // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
    'response_type' => 'array',
    /*
     * 接口请求相关配置，超时时间等，具体可用参数请参考：
     * https://github.com/symfony/symfony/blob/5.3/src/Symfony/Contracts/HttpClient/HttpClientInterface.php
     */
    'http' => [
        'throw' => env('WECHAT_HTTP_THROW_EXCEPTION', true), // 状态码非 200、300 时是否抛出异常，默认为开启
        'timeout' => 5.0,
        // 'base_uri' => 'https://api.weixin.qq.com/', // 如果你在国外想要覆盖默认的 url 的时候才使用，根据不同的模块配置不同的 uri

        'retry' => true, // 使用默认重试配置
        //  'retry' => [
        //      // 仅以下状态码重试
        //      'status_codes' => [429, 500]
        //       // 最大重试次数
        //      'max_retries' => 3,
        //      // 请求间隔 (毫秒)
        //      'delay' => 1000,
        //      // 如果设置，每次重试的等待时间都会增加这个系数
        //      // (例如. 首次:1000ms; 第二次: 3 * 1000ms; etc.)
        //      'multiplier' => 3
        //  ],
    ],
    'log' => [
        'level' => 'debug',
        'file' => BASE_PATH . '/runtime/logs/wechat.log',
    ],
];
