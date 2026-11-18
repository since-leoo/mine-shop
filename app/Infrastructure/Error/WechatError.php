<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum WechatError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case CODE_DIRECTORY_CREATE_FAILED = 550001;
    case OAUTH_RESPONSE_INVALID = 550002;
    case OAUTH_CONFIGURATION_MISSING = 550003;

    public function template(): string
    {
        return match ($this) {
            self::CODE_DIRECTORY_CREATE_FAILED => '无法创建小程序码目录',
            self::OAUTH_RESPONSE_INVALID => '微信授权用户响应类型无效',
            self::OAUTH_CONFIGURATION_MISSING => '请先在后台“系统集成”中配置微信授权 AppID 和 AppSecret',
        };
    }
}
