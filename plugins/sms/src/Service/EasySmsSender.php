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

namespace Plugin\Sms\Service;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;
use Overtrue\EasySms\EasySms;
use Overtrue\EasySms\Exceptions\InvalidArgumentException;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;
use Overtrue\EasySms\Strategies\OrderStrategy;
use Plugin\Sms\Model\SmsMessage;

final class EasySmsSender implements SmsSenderInterface
{
    public function __construct(private readonly DomainMallSettingService $mallSettingService) {}

    /**
     * @throws InvalidArgumentException
     * @throws NoGatewayAvailableException
     */
    public function send(SmsMessage $message): void
    {
        if (! class_exists(EasySms::class)) {
            throw new BusinessException(ResultCode::FAIL, '短信插件依赖 easy-sms 未安装');
        }

        $integration = $this->mallSettingService->integration();
        if (! $integration->isChannelEnabled('sms') || $integration->smsProvider() === 'disabled') {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, 'SMS service is disabled.');
        }

        $smsConfig = $integration->smsConfig();
        $payload = $message->toEasySmsPayload(
            (string) ($smsConfig['template_code'] ?? $smsConfig['template_id'] ?? $integration->smsTemplate())
        );
        $config = [
            'timeout' => 5.0,
            'default' => ['strategy' => OrderStrategy::class, 'gateways' => [$integration->smsProvider()]],
            'gateways' => [
                'aliyun' => [
                    'access_key_id' => (string) ($smsConfig['access_key_id'] ?? ''),
                    'access_key_secret' => (string) ($smsConfig['access_key_secret'] ?? ''),
                    'sign_name' => (string) ($smsConfig['sign_name'] ?? ''),
                ],
                'tencent' => [
                    'secret_id' => (string) $smsConfig['access_key_id'] ?? '',
                    'secret_key' => (string) $smsConfig['access_key_secret'] ?? '',
                    'sign_name' => (string) ($smsConfig['sign_name'] ?? ''),
                ],
            ],
        ];
        (new EasySms($config))->send($message->phone(), $payload);
    }
}
