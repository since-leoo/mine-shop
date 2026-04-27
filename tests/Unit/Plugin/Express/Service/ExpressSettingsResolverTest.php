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
            'company_name_map' => [],
        ], $resolver->toArray());
    }

    public function testItPrefersPersistedMallExpressSettings(): void
    {
        $persisted = [
            'mall.express.enabled' => false,
            'mall.express.default_provider' => 'kuaidi100',
            'mall.express.customer' => 'customer-1',
            'mall.express.key' => 'secret-1',
            'mall.express.endpoint' => 'https://example.com/query',
            'mall.express.cache_ttl' => 180,
            'mall.express.timeout' => 9,
            'mall.express.company_name_map' => [
                ['code' => 'shunfeng', 'name' => '顺丰速运'],
                ['code' => 'yto', 'name' => '圆通速递'],
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
            'company_name_map' => [
                'shunfeng' => '顺丰速运',
                'yto' => '圆通速递',
            ],
        ], $resolver->toArray());
    }
}
