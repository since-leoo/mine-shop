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
use App\Infrastructure\Error\SmsError;
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

    private const MAX_ATTEMPTS = 5;

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
        $this->assertInput($phone, $scene);
        $code = null;
        $reservation = null;
        try {
            $this->assertProductionSmsEnabled();
            $reservation = bin2hex(random_bytes(16));
            $this->reserveSend($phone, $scene, $reservation);
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            if (! $this->isNonProduction()) {
                $this->dispatchSms($phone, $code);
            }
            // 供应商成功后才发布验证码；慢响应也不能覆盖后来发送的新验证码。
            $this->storeVerificationCode($phone, $scene, $code, $reservation);
            $result = ['phone' => $phone, 'scene' => $scene];
            if ($this->isNonProduction()) {
                $result['code'] = $code;
                $this->logNonProductionCode($phone, $scene, $code);
            }
            $this->recordSmsMessage($phone, $scene, true);
            return $result;
        } catch (\Throwable $error) {
            if ($reservation !== null) {
                $this->releaseSend($phone, $scene, $reservation);
            }
            $this->recordSmsMessage($phone, $scene, false);
            throw $error;
        }
    }

    public function verifyCode(string $phone, string $scene, string $code): bool
    {
        $this->assertInput($phone, $scene);
        if (! preg_match('/^\d{6}$/D', $code)) return false;
        // 比较、错误计数和消费必须在同一条 Redis 操作中完成，避免并发重复登录。
        $script = <<<'LUA'
local code = redis.call('GET', KEYS[1])
if not code then return 0 end
if code ~= ARGV[1] then
    local attempts = redis.call('INCR', KEYS[2])
    redis.call('EXPIRE', KEYS[2], math.max(1, redis.call('TTL', KEYS[1])))
    if attempts >= tonumber(ARGV[2]) then redis.call('DEL', KEYS[1]) end
    return 0
end
redis.call('DEL', KEYS[1], KEYS[2])
return 1
LUA;
        return $this->redis()->eval($script, [
            $this->fullKey($this->codeKey($phone, $scene)), $this->fullKey($this->attemptKey($phone, $scene)),
            $code, self::MAX_ATTEMPTS,
        ], 2);
    }

    private function assertInput(string $phone, string $scene): void
    {
        if (! preg_match('/^1[3-9]\d{9}$/D', $phone) || ! in_array($scene, ['register', 'forgot_password', 'login'], true)) {
            throw SmsError::PHONE_OR_SCENE_INVALID->invalid();
        }
    }

    private function reserveSend(string $phone, string $scene, string $token): void
    {
        $script = <<<'LUA'
if redis.call('EXISTS', KEYS[1]) == 1 then return 0 end
if tonumber(redis.call('GET', KEYS[2]) or '0') >= tonumber(ARGV[3]) then return 0 end
redis.call('SET', KEYS[1], ARGV[1], 'EX', ARGV[2])
redis.call('INCR', KEYS[2])
redis.call('EXPIRE', KEYS[2], ARGV[4])
redis.call('SET', KEYS[3], ARGV[1], 'EX', ARGV[5])
redis.call('SET', KEYS[4], KEYS[2], 'EX', ARGV[5])
return 1
LUA;
        $reserved = $this->redis()->eval($script, [
            $this->fullKey($this->resendKey($phone, $scene)), $this->fullKey($this->dailyLimitKey($phone)),
            $this->fullKey($this->generationKey($phone, $scene)), $this->fullKey('reservation:' . $token),
            $token, self::RESEND_INTERVAL, self::DAILY_LIMIT, $this->secondsUntilDayEnd(), self::CODE_TTL,
        ], 4);
        if (! $reserved) {
            throw SmsError::VERIFICATION_RATE_LIMITED->exception();
        }
    }

    private function releaseSend(string $phone, string $scene, string $token): void
    {
        // 只退还本次发送预留，旧请求失败不能删除另一个请求的锁或扣减其额度。
        $script = <<<'LUA'
if redis.call('GET', KEYS[1]) == ARGV[1] then redis.call('DEL', KEYS[1]) end
if redis.call('GET', KEYS[3]) == ARGV[1] then redis.call('DEL', KEYS[3]) end
local reservedDay = redis.call('GET', KEYS[4])
redis.call('DEL', KEYS[4])
if reservedDay == KEYS[2] and tonumber(redis.call('GET', KEYS[2]) or '0') > 0 then
    redis.call('DECR', KEYS[2])
end
return 1
LUA;
        $this->redis()->eval($script, [
            $this->fullKey($this->resendKey($phone, $scene)), $this->fullKey($this->dailyLimitKey($phone)),
            $this->fullKey($this->generationKey($phone, $scene)), $this->fullKey('reservation:' . $token), $token,
        ], 4);
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
            throw new BusinessException(SmsError::SERVICE_DISABLED, resultCode: ResultCode::UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Store the verification code in the cache.
     */
    private function storeVerificationCode(string $phone, string $scene, string $code, string $token): void
    {
        $script = <<<'LUA'
if redis.call('GET', KEYS[1]) ~= ARGV[1] then return 0 end
redis.call('SET', KEYS[2], ARGV[2], 'EX', ARGV[3])
redis.call('DEL', KEYS[3], KEYS[4])
return 1
LUA;
        $stored = $this->redis()->eval($script, [
            $this->fullKey($this->generationKey($phone, $scene)), $this->fullKey($this->codeKey($phone, $scene)),
            $this->fullKey($this->attemptKey($phone, $scene)), $this->fullKey('reservation:' . $token),
            $token, $code, self::CODE_TTL,
        ], 4);
        if (! $stored) throw SmsError::VERIFICATION_SUPERSEDED->exception();
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
    private function recordSmsMessage(string $phone, string $scene, bool $success): void
    {
        $sentAt = Carbon::now();

        try {
            Message::query()->create([
                'title' => '短信验证码（' . $scene . '）',
                'content' => $success ? '短信验证码发送成功' : '短信验证码发送失败',
                'type' => 'system',
                'priority' => 1,
                'recipient_type' => 'all',
                'channels' => ['sms'],
                'sent_at' => $sentAt,
                'status' => $success ? 'sent' : 'failed',
                'remark' => '短信插件发送记录',
                'extra_data' => [
                    'phone' => $phone,
                    'scene' => $scene,
                    'status' => $success ? 'sent' : 'failed',
                ],
            ]);
        } catch (\Throwable $e) {
            // 记录失败不能覆盖短信发送本身的结果。
            try {
                logger()->warning('Unable to record SMS message', [
                    'phone' => $phone,
                    'scene' => $scene,
                    'error' => $e->getMessage(),
                ]);
            } catch (\Throwable) {
                // 日志设施不可用时仍保留原始短信发送结果。
            }
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
    private function fullKey(string $key): string
    {
        return $this->redis()->getPrefix() . ':' . $key;
    }

    private function attemptKey(string $phone, string $scene): string
    {
        return sprintf('attempt:%s:%s', $scene, $phone);
    }

    private function generationKey(string $phone, string $scene): string
    {
        return sprintf('generation:%s:%s', $scene, $phone);
    }

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
