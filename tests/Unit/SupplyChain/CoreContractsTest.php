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

namespace HyperfTests\Unit\SupplyChain;

use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use App\Domain\Infrastructure\Plugin\Contract\ExternalFulfillmentInterface;
use App\Domain\Infrastructure\Plugin\Service\DomainExternalFulfillmentService;
use App\Domain\Infrastructure\Plugin\ValueObject\ShipmentAllocationVo;
use App\Domain\Trade\AfterSale\ValueObject\AfterSaleAllowanceVo;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Entity\OrderItemEntity;
use App\Domain\Trade\Order\ValueObject\OrderPriceValue;
use App\Domain\Trade\Payment\DomainPayService;
use App\Infrastructure\Plugin\NullExternalFulfillment;
use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSource;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class CoreContractsTest extends TestCase
{
    public function testPaymentAutowiringUsesRegisteredCapabilityInsteadOfDefaultFallback(): void
    {
        $container = new Container(new DefinitionSource([]));
        $reflection = new \ReflectionClass(DomainPayService::class);
        $capability = $this->createMock(ExternalFulfillmentInterface::class);
        foreach ($reflection->getConstructor()->getParameters() as $parameter) {
            $type = $parameter->getType()->getName();
            $container->set($type, $type === ExternalFulfillmentInterface::class ? $capability : $this->createMock($type));
        }
        $payment = $container->get(DomainPayService::class);
        self::assertSame($capability, $reflection->getProperty('fulfillment')->getValue($payment));
    }

    public function testMissingPluginRejectsExternalFulfillmentButAllowsMerchant(): void
    {
        $capability = new NullExternalFulfillment();
        $capability->assertAvailable('merchant');
        self::assertSame(['items' => [], 'shipping_fee' => 0], $capability->prepare([], [], 'normal'));
        $this->expectException(\DomainException::class);
        $capability->assertAvailable('supply_chain', ['connection_id' => 2]);
    }

    public function testFrozenSupplierSnapshotSurvivesSubmissionSerialization(): void
    {
        $item = new OrderItemEntity();
        $item->setSkuId(7);
        $item->setQuantity(2);
        $snapshot = ['connection_id' => 2, 'external_sku_id' => 'ABC', 'binding_version' => 3];
        $item->freezeFulfillment('supply_chain', $snapshot);
        $restored = OrderItemEntity::fromPayload($item->toArray());
        self::assertSame('supply_chain', $restored->getFulfillmentProvider());
        self::assertSame($snapshot, $restored->getFulfillmentSnapshot());
    }

    public function testMissingFrozenSnapshotCannotBecomeMerchantOnRecovery(): void
    {
        $this->expectException(\DomainException::class);
        OrderItemEntity::fromPayload(['fulfillment_provider' => 'supply_chain', 'sku_id' => 7]);
    }

    public function testPackageTracksPartialQuantityAndRejectsOverShipping(): void
    {
        $items = [['id' => 10, 'quantity' => 3, 'fulfillment_provider' => 'supply_chain'], ['id' => 11, 'quantity' => 1, 'fulfillment_provider' => 'merchant']];
        $package = ['shipping_company' => '顺丰', 'shipping_no' => 'SF123'];
        $allocation = new ShipmentAllocationVo($items, [10 => 1], 'supply_chain', $package, [['order_item_id' => 10, 'quantity' => 2]]);
        self::assertSame('partial_shipped', $allocation->status);
        self::assertSame([10 => 2], $allocation->quantities);
        $this->expectException(\DomainException::class);
        new ShipmentAllocationVo($items, [10 => 2], 'supply_chain', $package, [['order_item_id' => 10, 'quantity' => 2]]);
    }

    public function testPackageCannotFulfillAnotherProviderItems(): void
    {
        $this->expectException(\DomainException::class);
        new ShipmentAllocationVo([['id' => 1, 'quantity' => 2, 'fulfillment_provider' => 'merchant']], [], 'supply_chain', ['shipping_company' => 'SF', 'shipping_no' => '1'], [['order_item_id' => 1, 'quantity' => 1]]);
    }

    public function testPartiallyShippedOrderCannotBeCompleted(): void
    {
        $order = new OrderEntity();
        $order->setStatus('partial_shipped');
        $this->expectException(\RuntimeException::class);
        $order->complete();
    }

    public function testRefundAllowanceAllocatesDiscountsAndSubtractsEarlierCases(): void
    {
        $items = [['id' => 1, 'quantity' => 3, 'total_price' => 100], ['id' => 2, 'quantity' => 1, 'total_price' => 200]];
        $first = new AfterSaleAllowanceVo($items, 1, 250, 1, 20);
        $second = new AfterSaleAllowanceVo($items, 2, 250, 0, 0);
        self::assertSame(2, $first->quantity);
        self::assertSame(63, $first->amount);
        self::assertSame(167, $second->amount);
        self::assertSame(250, $first->amount + 20 + $second->amount);
        $this->expectException(\DomainException::class);
        $first->assertRequest(1, 64);
    }

    public function testNoPluginImplementationClassLeaksIntoCoreBindings(): void
    {
        $source = file_get_contents(BASE_PATH . '/config/autoload/dependencies.php');
        self::assertStringNotContainsString('Plugin\SupplyChain', $source);
        foreach (glob(BASE_PATH . '/app/Domain/Infrastructure/Plugin/Contract/Commerce*Interface.php') as $file) {
            self::assertDoesNotMatchRegularExpression('/Infrastructure\\\Model|\bEntity\b/', file_get_contents($file));
        }
    }

    public function testExternalQuoteAddsOnlySupplierFreightAndFreezesBinding(): void
    {
        $catalog = $this->createMock(ProductSnapshotInterface::class);
        $catalog->method('getSkuSnapshots')->willReturn([7 => ['sku_id' => 7, 'fulfillment_provider' => 'supply_chain']]);
        $capability = $this->createMock(ExternalFulfillmentInterface::class);
        $binding = ['connection_id' => 2, 'external_sku_id' => 'ABC', 'binding_version' => 3];
        $capability->expects(self::once())->method('prepare')->with(
            [['sku_id' => 7, 'fulfillment_provider' => 'supply_chain', 'quantity' => 2]],
            [],
            'normal'
        )->willReturn(['items' => [7 => ['provider' => 'supply_chain', 'snapshot' => $binding]], 'shipping_fee' => 50]);
        $item = new OrderItemEntity();
        $item->setSkuId(7);
        $item->setQuantity(2);
        $item->attachSnapshot(['fulfillment_provider' => 'supply_chain']);
        $order = new OrderEntity();
        $order->setOrderType('normal');
        $order->setItems($item);
        $price = new OrderPriceValue();
        $price->setGoodsAmount(200);
        $price->setShippingFee(10);
        $order->setPriceDetail($price);
        (new DomainExternalFulfillmentService($capability, $catalog))->prepare($order);
        self::assertSame(60, $order->getShippingFee());
        self::assertSame($binding, $item->getFulfillmentSnapshot());
    }
}
