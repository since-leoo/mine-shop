<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum SmsError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case RECIPIENT_PHONE_REQUIRED = 540001;
    case DEPENDENCY_MISSING = 540002;
    case SERVICE_DISABLED = 540003;
    case PHONE_OR_SCENE_INVALID = 540004;
    case VERIFICATION_RATE_LIMITED = 540005;
    case VERIFICATION_SUPERSEDED = 540006;

    public function template(): string
    {
        return match ($this) {
            self::RECIPIENT_PHONE_REQUIRED => '短信收件人手机号不能为空',
            self::DEPENDENCY_MISSING => '短信插件依赖 easy-sms 未安装',
            self::SERVICE_DISABLED => '短信服务未启用',
            self::PHONE_OR_SCENE_INVALID => '短信手机号或场景无效',
            self::VERIFICATION_RATE_LIMITED => '验证码发送过于频繁或已达每日上限，请稍后再试',
            self::VERIFICATION_SUPERSEDED => '验证码已更新，请使用最新验证码',
        };
    }
}
