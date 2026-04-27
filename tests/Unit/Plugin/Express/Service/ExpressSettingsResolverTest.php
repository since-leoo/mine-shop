<?php

declare(strict_types=1);

namespace Tests\Unit\Plugin\Express\Service;

use App\Domain\Infrastructure\SystemSetting\Service\DomainSystemSettingService;
use PHPUnit\Framework\TestCase;
use Plugin\Express\Service\ExpressSettingsResolver;

/**
 * @internal
 * @coversNothing
 */
final class ExpressSettingsResolverTest extends TestCase
{
    public function testItReturnsDefaultsWhenSettingsAreMissing(): void
    {
        $settingService = $this->createMock(DomainSystemSettingService::class);
        $settingService->method('get')->willReturnCallback(
            static fn (string $key, mixed $default = null): mixed => $default
        );

        $resolver = new ExpressSettingsResolver($settingService);

        self::assertSame([
            'enabled' => true,
            'default_provider' => 'kuaidi100',
            'customer' => '',
            'key' => '',
            'endpoint' => 'https://poll.kuaidi100.com/poll/query.do',
            'cache_ttl' => 300,
            'timeout' => 5,
        ], $resolver->toArray());
    }

    public function testItPrefersPersistedShippingDialogSettings(): void
    {
        $persisted = [
            'mall.shipping.express_tracking_config' => [
                'enabled' => false,
                'default_provider' => 'kuaidi100',
                'customer' => 'customer-1',
                'key' => 'secret-1',
                'endpoint' => 'https://example.com/query',
                'cache_ttl' => 180,
                'timeout' => 9,
            ],
        ];

        $settingService = $this->createMock(DomainSystemSettingService::class);
        $settingService->method('get')->willReturnCallback(
            static fn (string $key, mixed $default = null): mixed => $persisted[$key] ?? $default
        );

        $resolver = new ExpressSettingsResolver($settingService);

        self::assertSame([
            'enabled' => false,
            'default_provider' => 'kuaidi100',
            'customer' => 'customer-1',
            'key' => 'secret-1',
            'endpoint' => 'https://example.com/query',
            'cache_ttl' => 180,
            'timeout' => 9,
        ], $resolver->toArray());
    }
}
