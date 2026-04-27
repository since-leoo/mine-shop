<?php

declare(strict_types=1);

namespace Plugin\Express\Service;

use App\Domain\Infrastructure\SystemSetting\Service\DomainSystemSettingService;

final class ExpressSettingsResolver
{
    public function __construct(private readonly DomainSystemSettingService $settingService) {}

    /**
     * @return array{
     *     enabled: bool,
     *     default_provider: string,
     *     customer: string,
     *     key: string,
     *     endpoint: string,
     *     cache_ttl: int,
     *     timeout: int,
     *     company_name_map: array<string, string>
     * }
     */
    public function toArray(): array
    {
        return [
            'enabled' => (bool) $this->settingService->get('mall.express.enabled', true),
            'default_provider' => (string) $this->settingService->get('mall.express.default_provider', 'kuaidi100'),
            'customer' => (string) $this->settingService->get('mall.express.customer', ''),
            'key' => (string) $this->settingService->get('mall.express.key', ''),
            'endpoint' => (string) $this->settingService->get('mall.express.endpoint', 'https://poll.kuaidi100.com/poll/query.do'),
            'cache_ttl' => (int) $this->settingService->get('mall.express.cache_ttl', 300),
            'timeout' => (int) $this->settingService->get('mall.express.timeout', 5),
            'company_name_map' => $this->normalizeCompanyNameMap(
                $this->settingService->get('mall.express.company_name_map', [])
            ),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function normalizeCompanyNameMap(mixed $value): array
    {
        if (! \is_array($value)) {
            return [];
        }

        $normalized = [];
        foreach ($value as $item) {
            if (! \is_array($item)) {
                continue;
            }

            $code = trim((string) ($item['code'] ?? ''));
            $name = trim((string) ($item['name'] ?? ''));

            if ($code === '' || $name === '') {
                continue;
            }

            $normalized[$code] = $name;
        }

        return $normalized;
    }
}
