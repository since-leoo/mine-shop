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

namespace Plugin\Sms;

use Plugin\Sms\Contract\SmsVerificationServiceInterface;
use Plugin\Sms\Service\EasySmsSender;
use Plugin\Sms\Service\EasySmsVerificationService;
use Plugin\Sms\Service\SmsSenderInterface;

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
                SmsSenderInterface::class => EasySmsSender::class,
            ],
        ];
    }
}
