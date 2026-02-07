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

namespace HyperfTests\Unit\Domain\Order;

use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Entity\OrderShipEntity;
use App\Domain\Infrastructure\SystemSetting\ValueObject\OrderSetting;
use App\Domain\Infrastructure\SystemSetting\ValueObject\ShippingSetting;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class OrderSettingsTraitTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        Carbon::setTestNow();
    }

    public function testApplySubmissionPolicySetsExpireTime(): void
    {
        Carbon::setTestNow(Carbon::create(2025, 1, 1, 10, 0, 0));

        $entity = new OrderEntity();
        $setting = new OrderSetting(45, 7, 15, true, 'system', '400-000-0000');

        $entity->applySubmissionPolicy($setting);

        self::assertSame('2025-01-01 10:45:00', $entity->getExpireTime()?->toDateTimeString());
    }

    public function testGuardPreorderAllowedRejectsWhenDisabled(): void
    {
        $entity = new OrderEntity();
        $entity->setOrderType('preorder');

        $this->expectException(\DomainException::class);
        $entity->guardPreorderAllowed(false);
    }

    public function testEnsureShippableValidatesSupportedProviders(): void
    {
        $entity = new OrderEntity();

        $ship = new OrderShipEntity();
        $ship->setPackages([
            ['shipping_company' => 'SF', 'shipping_no' => 'SF123456789'],
        ]);
        $entity->setShipEntity($ship);

        $setting = new ShippingSetting('express', true, '', 0, ['SF', 'YTO']);

        $entity->ensureShippable($setting);
        self::assertTrue(true);
    }

    public function testEnsureShippableRejectsUnsupportedProvider(): void
    {
        $entity = new OrderEntity();
        $ship = new OrderShipEntity();
        $ship->setPackages([
            ['shipping_company' => 'UNKNOWN', 'shipping_no' => 'ERR-001'],
        ]);
        $entity->setShipEntity($ship);

        $setting = new ShippingSetting('express', true, '', 0, ['SF']);

        $this->expectException(\DomainException::class);
        $entity->ensureShippable($setting);
    }
}
