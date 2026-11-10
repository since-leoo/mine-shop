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

namespace HyperfTests\Integration\SupplyChain;

use App\Application\Admin\Trade\AppAfterSaleRefundService;
use App\Application\Api\Order\AppOrderSubmissionService;
use App\Application\Api\Order\AppTradeRecoveryService;
use App\Application\Api\Payment\AppPaymentReconciliationService;
use App\Domain\Catalog\Product\Repository\ProductRepository;
use App\Domain\Catalog\Product\Service\DomainProductService;
use App\Domain\Infrastructure\Plugin\Repository\CommerceOrderRepository;
use App\Domain\Infrastructure\Plugin\Repository\CommerceProductRepository;
use App\Domain\Infrastructure\Plugin\Service\DomainCommerceOrderService;
use App\Domain\Infrastructure\Plugin\Service\DomainCommerceProductService;
use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\ContentSetting;
use App\Domain\Infrastructure\SystemSetting\ValueObject\ProductSetting;
use App\Domain\Trade\Order\Contract\StockReservationInterface;
use App\Domain\Trade\Order\Event\OrderPaidIntegrationEvent;
use App\Domain\Trade\Order\Repository\OrderPaymentRefundRepository;
use App\Domain\Trade\Order\Repository\OrderSubmissionRepository;
use App\Domain\Trade\Order\Service\DomainOrderService;
use App\Domain\Trade\Shared\Repository\TradeOutboxRepository;
use App\Infrastructure\Model\Product\Product;
use App\Infrastructure\Service\Trade\HyperfTransactionManager;
use HyperfTests\Support\DatabaseTestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;

/**
 * @internal
 * @coversNothing
 */
final class CorePersistenceTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach ([
            '2024_01_01_000004_create_mall_products_table.php' => 'CreateMallProductsTable',
            '2024_01_01_000005_create_mall_product_skus_table.php' => 'CreateMallProductSkusTable',
            '2024_01_01_000008_create_mall_orders_table.php' => 'CreateMallOrdersTable',
            '2024_01_01_000009_create_mall_order_items_table.php' => 'CreateMallOrderItemsTable',
            '2024_01_01_000010_create_mall_order_addresses_table.php' => 'CreateMallOrderAddressesTable',
            '2024_01_01_000011_create_mall_order_packages_table.php' => 'CreateMallOrderPackagesTable',
            '2024_01_01_000012_create_mall_order_package_items_table.php' => 'CreateMallOrderPackageItemsTable',
            '2024_01_01_000023_create_mall_product_attributes_table.php' => 'CreateMallProductAttributesTable',
            '2024_01_01_000024_create_mall_product_gallery_table.php' => 'CreateMallProductGalleryTable',
            '2026_03_16_000001_create_after_sale_tables.php' => 'CreateAfterSaleTables',
        ] as $file => $class) {
            require_once BASE_PATH . '/migrations/' . $file;
            (new $class())->up();
        }
        $this->migrate('migrations/2026_10_16_000003_add_external_commerce_contracts.php');
    }

    public function testNewTablesAndEveryNewColumnHaveDatabaseComments(): void
    {
        foreach (['commerce_product_imports', 'commerce_stock_projections'] as $table) {
            $row = $this->connection->selectOne('SELECT TABLE_COMMENT AS note FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?', [$this->connection->getDatabaseName(), $table]);
            self::assertNotEmpty($row->note);
            $rows = $this->connection->select('SELECT COLUMN_COMMENT AS note FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?', [$this->connection->getDatabaseName(), $table]);
            foreach ($rows as $row) {
                self::assertNotEmpty($row->note);
            }
        }
        foreach (['products' => ['fulfillment_provider'], 'order_items' => ['fulfillment_provider', 'fulfillment_snapshot'], 'order_packages' => ['fulfillment_provider', 'integration_key', 'integration_digest']] as $table => $columns) {
            foreach ($columns as $column) {
                $row = $this->connection->selectOne('SELECT COLUMN_COMMENT AS note FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?', [$this->connection->getDatabaseName(), $table, $column]);
                self::assertNotEmpty($row->note);
            }
        }
    }

    public function testImportIsIdempotentAndPreservesSkuIdentityDuringSync(): void
    {
        $settings = $this->createMock(DomainMallSettingService::class);
        $settings->method('product')->willReturn(new ProductSetting(true, 10, 0, false, []));
        $settings->method('content')->willReturn(new ContentSetting([], '', '', ''));
        $domain = new DomainProductService(new ProductRepository(new Product()), $settings);
        $service = new DomainCommerceProductService($domain, new CommerceProductRepository());
        $input = ['name' => '供货商品', 'category_id' => 1, 'skus' => [
            ['sku_code' => 'SC1', 'sale_price' => 100, 'cost_price' => 80, 'stock' => 100],
            ['sku_code' => 'SC2', 'sale_price' => 200, 'cost_price' => 160, 'stock' => 100],
        ]];
        $first = $this->connection->transaction(static fn () => $service->import('supply_chain', 'import:1', $input));
        $second = $this->connection->transaction(static fn () => $service->import('supply_chain', 'import:1', $input));
        self::assertTrue($first['created']);
        self::assertFalse($second['created']);
        self::assertSame($first['product_id'], $second['product_id']);
        self::assertSame('draft', $first['status']);
        self::assertSame(0, $first['skus'][0]['stock']);
        $skuId = $first['skus'][0]['sku_id'];
        $synced = $this->connection->transaction(static fn () => $service->sync($first['product_id'], 'supply_chain', ['skus' => [['id' => $skuId, 'sale_price' => 150, 'stock' => 900]]]));
        self::assertCount(2, $synced['skus']);
        self::assertSame($skuId, $synced['skus'][0]['sku_id']);
        self::assertSame(150, $synced['skus'][0]['sale_price']);
        self::assertSame(0, $synced['skus'][0]['stock']);
    }

    public function testAppendSkusKeepsExistingFieldsAndRejectsCrossProductCodes(): void
    {
        $settings = $this->createMock(DomainMallSettingService::class);
        $settings->method('product')->willReturn(new ProductSetting(true, 10, 0, false, []));
        $settings->method('content')->willReturn(new ContentSetting([], '', '', ''));
        $domain = new DomainProductService(new ProductRepository(new Product()), $settings);
        $service = new DomainCommerceProductService($domain, new CommerceProductRepository());
        $first = $this->connection->transaction(static fn () => $service->import('supply_chain', 'append:1', ['name' => '增规商品', 'category_id' => 1, 'skus' => [['sku_code' => 'KEEP', 'sale_price' => 120]]]));
        $this->connection->table('products')->where('id', $first['product_id'])->update(['status' => 'active']);
        $this->connection->table('product_skus')->where('id', $first['skus'][0]['sku_id'])->update(['stock' => 7]);
        $input = [['sku_code' => 'KEEP', 'sale_price' => 999, 'stock' => 999], ['sku_code' => 'ADD', 'sale_price' => 150, 'stock' => 999]];
        $added = $this->connection->transaction(static fn () => $service->appendSkus($first['product_id'], 'supply_chain', $input));
        $again = $this->connection->transaction(static fn () => $service->appendSkus($first['product_id'], 'supply_chain', $input));
        self::assertSame('active', $added['status']);
        self::assertCount(2, $again['skus']);
        self::assertSame(array_column($added['skus'], 'sku_id'), array_column($again['skus'], 'sku_id'));
        self::assertSame(120, $added['skus'][0]['sale_price']);
        self::assertSame(7, $added['skus'][0]['stock']);
        self::assertSame(0, $added['skus'][1]['stock']);
        $other = $this->connection->transaction(static fn () => $service->import('supply_chain', 'append:2', ['name' => '其他商品', 'category_id' => 1, 'skus' => [['sku_code' => 'OTHER', 'sale_price' => 100]]]));
        $this->expectException(\DomainException::class);
        $this->connection->transaction(static fn () => $service->appendSkus($other['product_id'], 'supply_chain', [['sku_code' => 'ADD', 'sale_price' => 100]]));
    }

    public function testPartialPackagesAreIdempotentAndMerchantCompletesMixedOrder(): void
    {
        $this->connection->table('orders')->insert(['id' => 1, 'order_no' => 'O1', 'member_id' => 1, 'status' => 'paid', 'pay_status' => 'paid', 'goods_amount' => 400, 'total_amount' => 400, 'pay_amount' => 400]);
        foreach ([[10, 'supply_chain', 3], [11, 'merchant', 1]] as [$id, $provider, $qty]) {
            $this->connection->table('order_items')->insert(['id' => $id, 'order_id' => 1, 'product_id' => $id, 'sku_id' => $id, 'product_name' => '测试商品', 'sku_name' => '测试SKU', 'quantity' => $qty, 'unit_price' => 100, 'total_price' => $qty * 100, 'fulfillment_provider' => $provider, 'fulfillment_snapshot' => json_encode(['connection_id' => 1])]);
        }
        $service = new DomainCommerceOrderService(new CommerceOrderRepository());
        $package = ['shipping_company' => 'SF', 'shipping_no' => 'SF1'];
        $items = [['order_item_id' => 10, 'quantity' => 3]];
        $first = $this->connection->transaction(static fn () => $service->ship(1, 'supply_chain', 'P1', $package, $items));
        $again = $this->connection->transaction(static fn () => $service->ship(1, 'supply_chain', 'P1', $package, $items));
        self::assertTrue($first['created']);
        self::assertFalse($again['created']);
        self::assertSame('partial_shipped', $this->connection->table('orders')->value('status'));
        $this->connection->transaction(static fn () => $service->shipMerchantIfExternal(1, [['shipping_company' => 'YT', 'shipping_no' => 'YT1']]));
        self::assertSame('shipped', $this->connection->table('orders')->value('status'));
        self::assertSame(2, $this->connection->table('order_packages')->count());
        self::assertSame(4, (int) $this->connection->table('order_package_items')->sum('quantity'));
        $this->expectException(\DomainException::class);
        $this->connection->transaction(static fn () => $service->ship(1, 'supply_chain', 'P1', ['shipping_company' => 'SF', 'shipping_no' => 'CHANGED'], $items));
    }

    public function testPaidIntegrationListenerFailureRollsBackItsTaskAndOutboxAcknowledgement(): void
    {
        $outbox = new TradeOutboxRepository();
        $outbox->append('paid:EXT', 'order.paid', ['member_id' => 1, 'order_no' => 'EXT', 'order_id' => 1, 'amount' => 100]);
        $attempts = 0;
        $events = $this->createMock(EventDispatcherInterface::class);
        $events->method('dispatch')->willReturnCallback(function (object $event) use (&$attempts): object {
            if (! $event instanceof OrderPaidIntegrationEvent) {
                return $event;
            }
            $this->connection->table('commerce_product_imports')->insert(['provider' => 'test_listener', 'import_key' => hash('sha256', $event->orderNo)]);
            if (++$attempts === 1) {
                throw new \RuntimeException('listener interrupted after durable task write');
            }
            return $event;
        });
        $recovery = new AppTradeRecoveryService(
            new OrderSubmissionRepository(),
            $this->createMock(AppOrderSubmissionService::class),
            new OrderPaymentRefundRepository(),
            $this->createMock(AppAfterSaleRefundService::class),
            $outbox,
            $this->createMock(StockReservationInterface::class),
            $this->createMock(DomainOrderService::class),
            new HyperfTransactionManager(),
            $events,
            new NullLogger(),
            $this->createMock(AppPaymentReconciliationService::class),
        );
        $recovery->publish();
        self::assertSame(0, $this->connection->table('commerce_product_imports')->count());
        self::assertNull($this->connection->table('trade_outbox')->value('processed_at'));
        $recovery->publish();
        $recovery->publish();
        self::assertSame(1, $this->connection->table('commerce_product_imports')->count());
        self::assertSame(2, $attempts);
        self::assertNotNull($this->connection->table('trade_outbox')->value('processed_at'));
    }
}
