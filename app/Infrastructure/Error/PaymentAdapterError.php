<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum PaymentAdapterError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case CALLBACK_RESOURCE_MISSING = 320001;
    case WECHAT_CONFIG_INCOMPLETE = 320002;

    public function template(): string
    {
        return match ($this) {
            self::CALLBACK_RESOURCE_MISSING => '微信回调缺少已解密的 resource.ciphertext',
            self::WECHAT_CONFIG_INCOMPLETE => '微信支付配置不完整',
        };
    }
}
