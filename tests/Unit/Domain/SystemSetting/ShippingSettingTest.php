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

namespace HyperfTests\Unit\Domain\SystemSetting;

use App\Domain\SystemSetting\ValueObject\ShippingSetting;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class ShippingSettingTest extends TestCase
{
    public function testNewPropertiesHaveDefaults(): void
    {
        $setting = new ShippingSetting('express', true, '', 0, ['SF']);

        self::assertSame('free', $setting->defaultFreightType());
        self::assertSame(0, $setting->flatFreightAmount());
        self::assertFalse($setting->remoteAreaEnabled());
        self::assertSame(0, $setting->remoteAreaSurcharge());
        self::assertSame([], $setting->remoteAreaProvinces());
        self::assertSame([], $setting->defaultTemplateConfig());
    }

    public function testNewPropertiesWithExplicitValues(): void
    {
        $setting = new ShippingSetting(
            defaultMethod: 'express',
            enablePickup: false,
            pickupAddress: '北京市朝阳区',
            freeShippingThreshold: 9900,
            supportedProviders: ['SF', 'YTO'],
            defaultFreightType: 'flat',
            flatFreightAmount: 1500,
            remoteAreaEnabled: true,
            remoteAreaSurcharge: 2000,
            remoteAreaProvinces: ['西藏', '新疆'],
            defaultTemplateConfig: ['template_id' => 42],
        );

        self::assertSame('flat', $setting->defaultFreightType());
        self::assertSame(1500, $setting->flatFreightAmount());
        self::assertTrue($setting->remoteAreaEnabled());
        self::assertSame(2000, $setting->remoteAreaSurcharge());
        self::assertSame(['西藏', '新疆'], $setting->remoteAreaProvinces());
        self::assertSame(['template_id' => 42], $setting->defaultTemplateConfig());
    }

    public function testExistingPropertiesStillWork(): void
    {
        $setting = new ShippingSetting('express', true, '上海市', 5000, ['SF', 'YTO', 'ZTO']);

        self::assertSame('express', $setting->defaultMethod());
        self::assertTrue($setting->enablePickup());
        self::assertSame('上海市', $setting->pickupAddress());
        self::assertSame(5000, $setting->freeShippingThreshold());
        self::assertSame(['SF', 'YTO', 'ZTO'], $setting->supportedProviders());
        self::assertTrue($setting->isProviderSupported('SF'));
        self::assertFalse($setting->isProviderSupported('UNKNOWN'));
        self::assertFalse($setting->isProviderSupported(''));
    }
}
