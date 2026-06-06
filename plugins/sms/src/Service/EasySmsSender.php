<?php

declare(strict_types=1);

namespace Plugin\Sms\Service;

use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;
use Overtrue\EasySms\EasySms;

final class EasySmsSender implements SmsSenderInterface
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $config
     */
    public function send(string $phone, array $payload, array $config): void
    {
        if (! class_exists(EasySms::class)) {
            throw new BusinessException(ResultCode::FAIL, '短信插件依赖 easy-sms 未安装');
        }

        (new EasySms($config))->send($phone, $payload);
    }
}
