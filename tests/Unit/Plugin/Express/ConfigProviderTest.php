<?php

declare(strict_types=1);

namespace Tests\Unit\Plugin\Express;

use PHPUnit\Framework\TestCase;
use Plugin\Express\ConfigProvider;

/**
 * @internal
 * @coversNothing
 */
final class ConfigProviderTest extends TestCase
{
    public function testConfigProviderRegistersShippingDialogSetting(): void
    {
        $config = (new ConfigProvider())();

        self::assertArrayHasKey('mall', $config);
        self::assertArrayHasKey('groups', $config['mall']);
        self::assertArrayHasKey('shipping', $config['mall']['groups']);
        self::assertArrayHasKey('settings', $config['mall']['groups']['shipping']);
        self::assertArrayHasKey('mall.shipping.express_tracking_config', $config['mall']['groups']['shipping']['settings']);

        $setting = $config['mall']['groups']['shipping']['settings']['mall.shipping.express_tracking_config'];

        self::assertSame('json', $setting['type']);
        self::assertSame('form', $setting['meta']['component']);
        self::assertSame('dialog', $setting['meta']['display']);
        self::assertIsArray($setting['meta']['fields']);
    }
}
