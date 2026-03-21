<?php

declare(strict_types=1);

namespace Plugin\Sms;

use Plugin\Sms\Contract\SmsVerificationServiceInterface;
use Plugin\Sms\Service\EasySmsVerificationService;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'dependencies' => [
                SmsVerificationServiceInterface::class => EasySmsVerificationService::class,
            ],
        ];
    }
}