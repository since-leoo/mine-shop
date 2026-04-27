<?php

declare(strict_types=1);

namespace Plugin\Express\Service;

use App\Domain\Infrastructure\SystemSetting\Service\DomainSystemSettingService;

final class ExpressSettingsResolver
{
    private const SETTING_KEY = 'mall.shipping.express_tracking_config';

    public function __construct(private readonly DomainSystemSettingService $settingService) {}

    /**
     * @return array{
     *     enabled: bool,
     *     default_provider: string,
     *     customer: string,
     *     key: string,
     *     endpoint: string,
     *     cache_ttl: int,
     *     timeout: int
     * }
     */
    public function toArray(): array
    {
        $config = $this->normalizeConfig(
            $this->settingService->get(self::SETTING_KEY, $this->defaultConfig())
        );

        return [
            'enabled' => (bool) ($config['enabled'] ?? true),
            'default_provider' => (string) ($config['default_provider'] ?? 'kuaidi100'),
            'customer' => (string) ($config['customer'] ?? ''),
            'key' => (string) ($config['key'] ?? ''),
            'endpoint' => (string) ($config['endpoint'] ?? 'https://poll.kuaidi100.com/poll/query.do'),
            'cache_ttl' => (int) ($config['cache_ttl'] ?? 300),
            'timeout' => (int) ($config['timeout'] ?? 5),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultConfig(): array
    {
        return [
            'enabled' => true,
            'default_provider' => 'kuaidi100',
            'customer' => '',
            'key' => '',
            'endpoint' => 'https://poll.kuaidi100.com/poll/query.do',
            'cache_ttl' => 300,
            'timeout' => 5,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeConfig(mixed $value): array
    {
        return \is_array($value) ? array_replace($this->defaultConfig(), $value) : $this->defaultConfig();
    }
}
