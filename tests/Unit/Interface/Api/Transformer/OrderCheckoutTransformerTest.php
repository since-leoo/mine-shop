<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Interface\Api\Transformer;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\BasicSetting;
use App\Domain\Infrastructure\SystemSetting\ValueObject\OrderSetting;
use App\Domain\Infrastructure\SystemSetting\ValueObject\ShippingSetting;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Interface\Api\Transformer\OrderCheckoutTransformer;
use DG\BypassFinals;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class OrderCheckoutTransformerTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        BypassFinals::enable();
    }

    public function testTransformIncludesPickupShippingConfigWhenPickupEnabled(): void
    {
        $transformer = new OrderCheckoutTransformer($this->makeMallSettingService(
            defaultMethod: 'pickup',
            enablePickup: true,
            pickupAddress: 'Shanghai Store Counter',
        ));

        $data = $transformer->transform(new OrderEntity());

        self::assertSame('pickup', $data['shipping_config']['default_method']);
        self::assertSame([
            ['type' => 'express', 'name' => '快递配送', 'pickup_address' => ''],
            ['type' => 'pickup', 'name' => '门店自提', 'pickup_address' => 'Shanghai Store Counter'],
        ], $data['shipping_config']['methods']);
    }

    public function testTransformFallsBackToExpressWhenPickupDefaultIsDisabled(): void
    {
        $transformer = new OrderCheckoutTransformer($this->makeMallSettingService(
            defaultMethod: 'pickup',
            enablePickup: false,
            pickupAddress: 'Shanghai Store Counter',
        ));

        $data = $transformer->transform(new OrderEntity());

        self::assertSame('express', $data['shipping_config']['default_method']);
        self::assertSame([
            ['type' => 'express', 'name' => '快递配送', 'pickup_address' => ''],
        ], $data['shipping_config']['methods']);
    }

    private function makeMallSettingService(
        string $defaultMethod,
        bool $enablePickup,
        string $pickupAddress,
    ): DomainMallSettingService {
        $mallSettingService = $this->createMock(DomainMallSettingService::class);

        $mallSettingService
            ->method('basic')
            ->willReturn(new BasicSetting(
                mallName: 'MineMall',
                adminLogo: '/admin-logo.svg',
                adminSmallLogo: '/admin-small-logo.svg',
                loginLogo: '/login-logo.svg',
                miniappLogo: '/miniapp-logo.svg',
                favicon: '/favicon.ico',
                logo: '/logo.svg',
                userAgreement: '',
                privacyPolicy: '',
                supportEmail: 'support@example.com',
                hotline: '400-888-0000',
            ));

        $mallSettingService
            ->method('order')
            ->willReturn(new OrderSetting(30, 7, 15, true, 'system', '400-888-1000'));

        $mallSettingService
            ->method('shipping')
            ->willReturn(new ShippingSetting(
                defaultMethod: $defaultMethod,
                enablePickup: $enablePickup,
                pickupAddress: $pickupAddress,
                freeShippingThreshold: 0,
                supportedProviders: ['sf'],
            ));

        return $mallSettingService;
    }
}
