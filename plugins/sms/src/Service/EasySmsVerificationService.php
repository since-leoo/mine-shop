<?php

declare(strict_types=1);

namespace Plugin\Sms\Service;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Infrastructure\Abstract\ICache;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;
use Overtrue\EasySms\EasySms;
use Overtrue\EasySms\Strategies\OrderStrategy;
use Plugin\Sms\Contract\SmsVerificationServiceInterface;

final class EasySmsVerificationService implements SmsVerificationServiceInterface
{
    private const CODE_TTL = 300;
    private const RESEND_INTERVAL = 60;
    private const DAILY_LIMIT = 10;
    private const CACHE_PREFIX = '/plugin/sms/verification';

    public function __construct(
        private readonly DomainMallSettingService $mallSettingService,
        private readonly ICache $cache,
    ) {}

    public function sendCode(string $phone, string $scene): array
    {
        $this->assertCanSend($phone, $scene);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->storeVerificationCode($phone, $scene, $code);

        $result = [
            'phone' => $phone,
            'scene' => $scene,
        ];

        if ($this->isNonProduction()) {
            $result['code'] = $code;
            logger()->info('sms verification code generated in non-production mode', compact('phone', 'scene', 'code'));
            return $result;
        }

        $this->dispatchSms($phone, $code);
        return $result;
    }

    public function verifyCode(string $phone, string $scene, string $code): bool
    {
        $cachedCode = (string) $this->redis()->get($this->codeKey($phone, $scene));
        if ($cachedCode === '' || ! hash_equals($cachedCode, $code)) {
            return false;
        }

        $this->redis()->delete($this->codeKey($phone, $scene));
        return true;
    }

    private function assertCanSend(string $phone, string $scene): void
    {
        if ($this->redis()->get($this->resendKey($phone, $scene)) !== null) {
            // throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '发送过于频繁，请稍后再试');
        }

        $dailyCount = (int) ($this->redis()->get($this->dailyLimitKey($phone)) ?? 0);
        if ($dailyCount >= self::DAILY_LIMIT) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '今日验证码发送次数已达上限');
        }
    }

    private function storeVerificationCode(string $phone, string $scene, string $code): void
    {
        $this->redis()->set($this->codeKey($phone, $scene), $code, ['EX' => self::CODE_TTL]);
        $this->redis()->set($this->resendKey($phone, $scene), (string) time(), ['EX' => self::RESEND_INTERVAL]);

        $dailyCount = (int) ($this->redis()->get($this->dailyLimitKey($phone)) ?? 0);
        $this->redis()->set($this->dailyLimitKey($phone), (string) ($dailyCount + 1), ['EX' => $this->secondsUntilDayEnd()]);
    }

    private function dispatchSms(string $phone, string $code): void
    {
        $integration = $this->mallSettingService->integration();
        if ($integration->smsProvider() === 'disabled' || ! $integration->isChannelEnabled('sms')) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '短信服务未启用');
        }

        if (! class_exists(EasySms::class)) {
            throw new BusinessException(ResultCode::FAIL, '短信插件依赖 easy-sms 未安装');
        }

        $sms = new EasySms($this->buildEasySmsConfig());
        $smsConfig = $integration->smsConfig();
        $template = (string) ($smsConfig['template_code'] ?? $smsConfig['template_id'] ?? $integration->smsTemplate());

        $payload = [
            'template' => $template,
            'data' => ['code' => $code],
            'content' => str_replace(['{{$code}}', '{$code}'], $code, $integration->smsTemplate()),
        ];

        $sms->send($phone, $payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildEasySmsConfig(): array
    {
        $integration = $this->mallSettingService->integration();
        $provider = $integration->smsProvider();
        $smsConfig = $integration->smsConfig();

        return [
            'timeout' => 5.0,
            'default' => [
                'strategy' => OrderStrategy::class,
                'gateways' => [$provider],
            ],
            'gateways' => [
                'aliyun' => [
                    'access_key_id' => (string) ($smsConfig['access_key_id'] ?? ''),
                    'access_key_secret' => (string) ($smsConfig['access_key_secret'] ?? ''),
                    'sign_name' => (string) ($smsConfig['sign_name'] ?? ''),
                ],
                'tencent' => [
                    'sdk_app_id' => (string) ($smsConfig['sdk_app_id'] ?? $smsConfig['app_id'] ?? ''),
                    'secret_id' => (string) ($smsConfig['secret_id'] ?? $smsConfig['access_key_id'] ?? ''),
                    'secret_key' => (string) ($smsConfig['secret_key'] ?? $smsConfig['access_key_secret'] ?? ''),
                    'sign_name' => (string) ($smsConfig['sign_name'] ?? ''),
                ],
            ],
        ];
    }

    private function isNonProduction(): bool
    {
        return env('APP_ENV', 'dev') !== 'production';
    }

    private function secondsUntilDayEnd(): int
    {
        $tomorrow = strtotime('tomorrow');
        return max(60, $tomorrow - time());
    }

    private function redis(): ICache
    {
        return $this->cache->setPrefix(self::CACHE_PREFIX);
    }

    private function codeKey(string $phone, string $scene): string
    {
        return sprintf('code:%s:%s', $scene, $phone);
    }

    private function resendKey(string $phone, string $scene): string
    {
        return sprintf('rate:%s:%s', $scene, $phone);
    }

    private function dailyLimitKey(string $phone): string
    {
        return sprintf('daily:%s:%s', date('Ymd'), $phone);
    }
}