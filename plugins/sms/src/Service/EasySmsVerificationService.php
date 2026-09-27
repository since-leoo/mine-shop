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
use App\Infrastructure\Abstract\ICache;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;
use Carbon\Carbon;
use Plugin\Sms\Contract\SmsVerificationServiceInterface;
use Plugin\Sms\Model\SmsMessage;
use Plugin\SystemMessage\Infrastructure\Model\SystemMessage\Message;
use Random\RandomException;

final class EasySmsVerificationService implements SmsVerificationServiceInterface
{
    private const CODE_TTL = 300;

    private const RESEND_INTERVAL = 60;

    private const DAILY_LIMIT = 10;

    private const CACHE_PREFIX = '/plugin/sms/verification';

    public function __construct(
        private readonly DomainMallSettingService $mallSettingService,
        private readonly ICache $cache,
        private readonly ?SmsSenderInterface $sender = null,
    ) {}

    /**
     * Send an SMS verification code.
     *
     * @return array{
     *     phone: string,
     *     scene: string,
     *     code?: string
     * }
     * @throws RandomException
     * @throws \Throwable
     */
    public function sendCode(string $phone, string $scene): array
    {
        $code = null;

        try {
            $this->assertProductionSmsEnabled();
            $this->assertCanSend($phone, $scene);

            $code = mb_str_pad((string) random_int(0, 999999), 6, '0', \STR_PAD_LEFT);
            $this->storeVerificationCode($phone, $scene, $code);

            $result = [
                'phone' => $phone,
                'scene' => $scene,
            ];

            // Non-production mode
            if ($this->isNonProduction()) {
                $result['code'] = $code;
                $this->logNonProductionCode($phone, $scene, $code);
                $this->recordSmsMessage($phone, $scene, $code, true);

                return $result;
            }

            // Production mode
            $this->dispatchSms($phone, $code);
            $this->recordSmsMessage($phone, $scene, $code, true);

            return $result;
        } catch (\Throwable $e) {
            $this->recordSmsMessage($phone, $scene, $code, false, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verify the SMS verification code.
     */
    public function verifyCode(string $phone, string $scene, string $code): bool
    {
        // Verify the SMS verification code.
        $cachedCode = (string) $this->redis()->get($this->codeKey($phone, $scene));
        if ($cachedCode === '' || ! hash_equals($cachedCode, $code)) {
            return false;
        }

        $this->redis()->delete($this->codeKey($phone, $scene));

        return true;
    }

    /**
     * Assert that the SMS verification code can be sent.
     */
    private function assertCanSend(string $phone, string $scene): void
    {
        // Assert that the SMS verification code can be sent.
        if ($this->redis()->get($this->resendKey($phone, $scene)) !== null) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, 'SMS verification code was sent too frequently.');
        }

        $dailyCount = (int) ($this->redis()->get($this->dailyLimitKey($phone)) ?? 0);
        if ($dailyCount >= self::DAILY_LIMIT) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, 'Daily SMS verification code limit reached.');
        }
    }

    /**
     * Assert that production SMS is enabled.
     */
    private function assertProductionSmsEnabled(): void
    {
        // Assert that production SMS is enabled.
        if ($this->isNonProduction()) {
            return;
        }

        $integration = $this->mallSettingService->integration();
        if ($integration->smsProvider() === 'disabled' || ! $integration->isChannelEnabled('sms')) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, 'SMS service is disabled.');
        }
    }

    /**
     * Store the verification code in the cache.
     */
    private function storeVerificationCode(string $phone, string $scene, string $code): void
    {
        $this->redis()->set($this->codeKey($phone, $scene), $code, ['EX' => self::CODE_TTL]);
        $this->redis()->set($this->resendKey($phone, $scene), (string) time(), ['EX' => self::RESEND_INTERVAL]);

        $dailyCount = (int) ($this->redis()->get($this->dailyLimitKey($phone)) ?? 0);
        $this->redis()->set($this->dailyLimitKey($phone), (string) ($dailyCount + 1), ['EX' => $this->secondsUntilDayEnd()]);
    }

    /**
     * Dispatch the SMS verification code to the given phone number.
     */
    private function dispatchSms(string $phone, string $code): void
    {
        $integration = $this->mallSettingService->integration();
        $smsConfig = $integration->smsConfig();
        $template = (string) ($smsConfig['template_code'] ?? $smsConfig['template_id'] ?? $integration->smsTemplate());

        $content = str_replace(['{{$code}}', '{$code}'], $code, $integration->smsTemplate());
        ($this->sender ?? new EasySmsSender($this->mallSettingService))->send(new SmsMessage($phone, ['code' => $code], $template, $content));
    }

    /**
     * Record the SMS message.
     */
    private function recordSmsMessage(string $phone, string $scene, ?string $code, bool $success, ?string $failureReason = null): void
    {
        $sentAt = Carbon::now();

        try {
            Message::create([
                'title' => '短信验证码（' . $scene . '）',
                'content' => $code === null ? '短信验证码发送失败' : '短信验证码：' . $code,
                'type' => 'system',
                'priority' => 1,
                'recipient_type' => 'all',
                'channels' => ['sms'],
                'sent_at' => $sentAt,
                'status' => $success ? 'sent' : 'failed',
                'remark' => '短信插件发送记录',
                'extra_data' => [
                    'phone' => $phone,
                    'code' => $code,
                    'scene' => $scene,
                    'sent_at' => $sentAt->toDateTimeString(),
                    'status' => $success ? 'sent' : 'failed',
                    'failure_reason' => $failureReason,
                    'provider' => $this->smsProvider(),
                ],
            ]);
        } catch (\Throwable $e) {
            // 记录失败不能覆盖短信发送本身的结果。
            logger()->warning('Unable to record SMS message', [
                'phone' => $phone,
                'scene' => $scene,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the SMS provider.
     */
    private function smsProvider(): string
    {
        try {
            return $this->mallSettingService->integration()->smsProvider();
        } catch (\Throwable) {
            return 'unknown';
        }
    }

    /**
     * Determine if the current environment is non-production.
     */
    private function isNonProduction(): bool
    {
        return env('APP_ENV', 'dev') !== 'production';
    }

    /**
     * Log the SMS verification code in non-production mode.
     */
    private function logNonProductionCode(string $phone, string $scene, string $code): void
    {
        if (! \function_exists('logger')) {
            return;
        }

        try {
            logger()->info('sms verification code generated in non-production mode', compact('phone', 'scene', 'code'));
        } catch (\Throwable) {
        }
    }

    /**
     * Get the number of seconds until the end of the day.
     */
    private function secondsUntilDayEnd(): int
    {
        $tomorrow = strtotime('tomorrow');

        return max(60, $tomorrow - time());
    }

    /**
     * Get the cache instance with the plugin cache prefix.
     */
    private function redis(): ICache
    {
        return $this->cache->setPrefix(self::CACHE_PREFIX);
    }

    /**
     * Generate the cache key for the verification code.
     */
    private function codeKey(string $phone, string $scene): string
    {
        return \sprintf('code:%s:%s', $scene, $phone);
    }

    /**
     * Generate the cache key for the rate limit.
     */
    private function resendKey(string $phone, string $scene): string
    {
        return \sprintf('rate:%s:%s', $scene, $phone);
    }

    /**
     * Generate the cache key for the daily limit.
     */
    private function dailyLimitKey(string $phone): string
    {
        return \sprintf('daily:%s:%s', date('Ymd'), $phone);
    }
}
