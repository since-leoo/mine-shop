<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\Shipping\Service;

use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\ShippingSetting;
use App\Domain\Trade\Order\Entity\OrderItemEntity;
use App\Domain\Trade\Shipping\Repository\ShippingTemplateRepository;
use App\Domain\Trade\Shipping\Service\FreightCalculationService;
use DG\BypassFinals;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class FreightCalculationServiceTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        BypassFinals::enable();
    }

    public function testCalculateForItemsReturnsZeroWhenTotalReachesFreeShippingThreshold(): void
    {
        $service = $this->makeService(freeShippingThreshold: 9900, flatFreightAmount: 1200);

        $freight = $service->calculateForItems([
            $this->makeItem(productId: 10, unitPrice: 9900, quantity: 1),
        ], 'Shanghai');

        self::assertSame(0, $freight);
    }

    public function testCalculateForItemsKeepsFreightWhenTotalBelowFreeShippingThreshold(): void
    {
        $service = $this->makeService(freeShippingThreshold: 9900, flatFreightAmount: 1200);

        $freight = $service->calculateForItems([
            $this->makeItem(productId: 10, unitPrice: 9800, quantity: 1),
        ], 'Shanghai');

        self::assertSame(1200, $freight);
    }

    private function makeService(int $freeShippingThreshold, int $flatFreightAmount): FreightCalculationService
    {
        $mallSettingService = $this->createMock(DomainMallSettingService::class);
        $templateRepository = $this->createMock(ShippingTemplateRepository::class);
        $snapshotService = $this->createMock(ProductSnapshotInterface::class);

        $mallSettingService
            ->method('shipping')
            ->willReturn(new ShippingSetting(
                defaultMethod: 'express',
                enablePickup: true,
                pickupAddress: 'Store counter',
                freeShippingThreshold: $freeShippingThreshold,
                supportedProviders: ['sf'],
                defaultFreightType: 'flat',
                flatFreightAmount: $flatFreightAmount,
            ));

        $snapshotService
            ->method('getProduct')
            ->willReturn([
                'freight_type' => 'default',
                'flat_freight_amount' => 0,
                'shipping_template_id' => null,
            ]);

        return new FreightCalculationService($mallSettingService, $templateRepository, $snapshotService);
    }

    private function makeItem(int $productId, int $unitPrice, int $quantity): OrderItemEntity
    {
        $item = new OrderItemEntity();
        $item->setProductId($productId);
        $item->setUnitPrice($unitPrice);
        $item->setQuantity($quantity);

        return $item;
    }
}
