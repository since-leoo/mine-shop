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

namespace App\Domain\Infrastructure\SystemSetting\ValueObject;

/**
 * 系统集成配置值对象.
 */
final class IntegrationSetting
{
    /**
     * @param array<string, bool> $notificationChannels
     * @param array<string, mixed> $smsConfig
     */
    public function __construct(
        private readonly string $smsProvider,
        private readonly array $smsConfig,
        private readonly array $notificationChannels,
        private readonly string $smsTemplate,
        private readonly string $emailTemplate,
        private readonly string $webhookUrl,
        private readonly bool $auditMode,
    ) {}

    public function smsProvider(): string
    {
        return $this->smsProvider;
    }

    /**
     * @return array<string, mixed>
     */
    public function smsConfig(): array
    {
        return $this->smsConfig;
    }

    /**
     * @return array<string, bool>
     */
    public function notificationChannels(): array
    {
        return $this->notificationChannels;
    }

    public function isChannelEnabled(string $channel): bool
    {
        return (bool) ($this->notificationChannels[$channel] ?? false);
    }

    public function smsTemplate(): string
    {
        return $this->smsTemplate;
    }

    public function emailTemplate(): string
    {
        return $this->emailTemplate;
    }

    public function webhookUrl(): string
    {
        return $this->webhookUrl;
    }

    public function auditMode(): bool
    {
        return $this->auditMode;
    }
}
