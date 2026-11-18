<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum ExpressError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case PROVIDER_CONFIGURATION_MISSING = 560001;
    case PROVIDER_RESPONSE_INVALID = 560002;
    case PLUGIN_DISABLED = 560003;
    case PROVIDER_UNSUPPORTED = 560004;
    case PROVIDER_REJECTED = 560005;

    public function template(): string
    {
        return match ($this) {
            self::PROVIDER_CONFIGURATION_MISSING => '快递100配置不完整',
            self::PROVIDER_RESPONSE_INVALID => '快递100返回了无效响应',
            self::PLUGIN_DISABLED => '物流查询插件未启用',
            self::PROVIDER_UNSUPPORTED => '不支持的物流服务商',
            self::PROVIDER_REJECTED => '物流查询失败（服务商错误码：%s）：%s',
        };
    }
}
