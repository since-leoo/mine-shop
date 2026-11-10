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

use App\Application\Admin\Trade\AppAfterSaleCommandService;
use App\Application\Admin\Trade\AppAfterSaleRefundService;
use App\Application\Admin\Trade\AppOrderManualPaymentService;
use App\Application\Api\AfterSale\AppApiAfterSaleCommandService;
use App\Application\Api\Order\AppApiOrderCommandService;
use App\Application\Api\Order\AppOrderCancellationService;
use App\Application\Api\Order\AppOrderSubmissionService;
use App\Application\Api\Order\AppTradeRecoveryService;
use App\Application\Api\Payment\AppPaymentReconciliationService;
use App\Application\Commad\Plugin\AppCommerceAfterSaleService;
use App\Application\Commad\Plugin\AppCommerceInventoryService;
use App\Application\Commad\Plugin\AppCommerceOrderService;
use App\Application\Commad\Plugin\AppCommerceProductService;
use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use App\Domain\Catalog\Product\Repository\ProductRepository;
use App\Domain\Catalog\Product\Service\DomainProductService;
use App\Domain\Catalog\Product\Service\DomainProductSnapshotService;
use App\Domain\Infrastructure\Plugin\Contract\ExternalFulfillmentInterface;
use App\Domain\Infrastructure\Plugin\Repository\CommerceInventoryRepository;
use App\Domain\Infrastructure\Plugin\Repository\CommerceOrderRepository;
use App\Domain\Infrastructure\Plugin\Repository\CommerceProductRepository;
use App\Domain\Infrastructure\Plugin\Service\DomainCommerceAfterSaleService;
use App\Domain\Infrastructure\Plugin\Service\DomainCommerceInventoryService;
use App\Domain\Infrastructure\Plugin\Service\DomainCommerceOrderService;
use App\Domain\Infrastructure\Plugin\Service\DomainCommerceProductService;
use App\Domain\Infrastructure\Plugin\Service\DomainExternalFulfillmentService;
use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\ContentSetting;
use App\Domain\Infrastructure\SystemSetting\ValueObject\ProductSetting;
use App\Domain\Member\Repository\MemberWalletTransactionRepository;
use App\Domain\Member\Service\DomainMemberAddressService;
use App\Domain\Member\Service\DomainMemberWalletService;
use App\Domain\Trade\AfterSale\Contract\AfterSaleApplyInput;
use App\Domain\Trade\AfterSale\Repository\AfterSaleRepository;
use App\Domain\Trade\AfterSale\Service\DomainAfterSaleService;
use App\Domain\Trade\GroupBuy\Repository\GroupBuyOrderRepository;
use App\Domain\Trade\Order\Api\Command\DomainApiOrderCommandService;
use App\Domain\Trade\Order\Contract\CouponServiceInterface;
use App\Domain\Trade\Order\Entity\OrderItemEntity;
use App\Domain\Trade\Order\Event\OrderCancelledIntegrationEvent;
use App\Domain\Trade\Order\Event\OrderPaidIntegrationEvent;
use App\Domain\Trade\Order\Factory\OrderTypeStrategyFactory;
use App\Domain\Trade\Order\Repository\OrderPaymentRefundRepository;
use App\Domain\Trade\Order\Repository\OrderPaymentRepository;
use App\Domain\Trade\Order\Repository\OrderRepository;
use App\Domain\Trade\Order\Repository\OrderSubmissionRepository;
use App\Domain\Trade\Order\Service\DomainOrderCancellationService;
use App\Domain\Trade\Order\Service\DomainOrderPaymentService;
use App\Domain\Trade\Order\Service\DomainOrderService;
use App\Domain\Trade\Order\Service\OrderPendingCacheService;
use App\Domain\Trade\Payment\Contract\PaymentGatewayInterface;
use App\Domain\Trade\Payment\DomainPayService;
use App\Domain\Trade\Payment\ValueObject\PaymentPolicy;
use App\Domain\Trade\Seckill\Repository\SeckillOrderRepository;
use App\Domain\Trade\Shared\Repository\TradeOutboxRepository;
use App\Infrastructure\Model\AfterSale\AfterSale;
use App\Infrastructure\Model\Order\Order;
use App\Infrastructure\Model\Order\OrderPayment;
use App\Infrastructure\Model\Product\Product;
use App\Infrastructure\Service\Pay\WechatPayConfigResolver;
use App\Infrastructure\Service\Trade\ExternalInventoryProjection;
use App\Infrastructure\Service\Trade\HyperfTransactionManager;
use App\Infrastructure\Service\Trade\RedisStockReservation;
use App\Interface\Admin\Dto\Order\OrderManualPayDto;
use Hyperf\Config\Config;
use Hyperf\Event\EventDispatcher;
use Hyperf\Event\ListenerProvider;
use HyperfTests\Support\DatabaseTestCase;
use HyperfTests\Support\IsolatedRedis;
use Plugin\SupplyChain\Application\Commad\AppSupplyAfterSaleService;
use Plugin\SupplyChain\Application\Commad\AppSupplyTradeService;
use Plugin\SupplyChain\Domain\Repository\SupplyRepository;
use Plugin\SupplyChain\Domain\Service\DomainSupplyAfterSaleService;
use Plugin\SupplyChain\Domain\Service\DomainSupplyCheckoutService;
use Plugin\SupplyChain\Domain\Service\DomainSupplyProductService;
use Plugin\SupplyChain\Domain\Service\DomainSupplyPurchaseService;
use Plugin\SupplyChain\Domain\Service\DomainSupplyTaskService;
use Plugin\SupplyChain\Infrastructure\Listener\SupplyTradeListener;
use Plugin\SupplyChain\Interfaces\SupplyChainManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;

/**
 * Real plugin services -> public application ports -> real MySQL / Lua; no supplier network.
 * @internal
 * @coversNothing
 */
final class PluginCommerceBridgeTest extends DatabaseTestCase
{
    use IsolatedRedis;

    private SupplyRepository $repository;

    private DomainSupplyProductService $products;

    private DomainSupplyCheckoutService $checkout;

    private DomainSupplyPurchaseService $purchases;

    private AppCommerceProductService $catalog;

    private AppCommerceInventoryService $inventory;

    private AppCommerceOrderService $orders;

    private RedisStockReservation $reservations;

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
        $this->migrate('plugins/supply-chain/Database/Migrations/2026_10_16_000100_create_supply_chain_tables.php');
        $this->nativeRedis = $this->openRedis();
        $bridge = $this->redisBridge($this->nativeRedis);
        $config = new Config(['cache' => ['default' => ['prefix' => '']]]);
        $this->reservations = new RedisStockReservation($bridge, $config);
        $settings = $this->createMock(DomainMallSettingService::class);
        $settings->method('product')->willReturn(new ProductSetting(true, 10, 0, false, []));
        $settings->method('content')->willReturn(new ContentSetting([], '', '', ''));
        // Only cache effects and the unused network manager are mocked. All business adapters are real.
        $snapshots = $this->createMock(DomainProductSnapshotService::class);
        $transactions = new HyperfTransactionManager();
        $this->catalog = new AppCommerceProductService(new DomainCommerceProductService(new DomainProductService(new ProductRepository(new Product()), $settings), new CommerceProductRepository()), $transactions, $snapshots);
        $this->inventory = new AppCommerceInventoryService(new DomainCommerceInventoryService(new CommerceInventoryRepository(new ExternalInventoryProjection($bridge, $config))), $transactions, $snapshots);
        $this->orders = new AppCommerceOrderService(new DomainCommerceOrderService(new CommerceOrderRepository()), $transactions);
        $this->repository = new SupplyRepository();
        $tasks = new DomainSupplyTaskService($this->repository);
        $this->products = new DomainSupplyProductService($this->repository, $tasks, $this->catalog, $this->inventory);
        $this->checkout = new DomainSupplyCheckoutService($this->repository, $this->createMock(SupplyChainManagerInterface::class));
        $this->purchases = new DomainSupplyPurchaseService($this->repository, $tasks, $this->orders, $this->inventory);
        foreach ([1, 2] as $id) {
            $this->repository->insert('connections', ['id' => $id, 'name' => '测试连接' . $id, 'api_domain' => 'https://supplier.invalid', 'app_key' => 'test', 'app_secret_ciphertext' => 'test-only-unused', 'mode' => 'active']);
        }
    }

    protected function tearDown(): void
    {
        try {
            $this->cleanRedis();
        } finally {
            parent::tearDown();
        }
    }

    public function testImportCreatesUsableMappingsAndRefreshDoesNotOverwriteReservation(): void
    {
        $result = $this->import(1, ['A', 'B'], 1);
        $product = $this->catalog->find($result['product_id']);
        self::assertSame('draft', $product['status']);
        self::assertSame('supply_chain', $product['fulfillment_provider']);
        self::assertCount(2, $product['skus']);
        $skuId = (int) $product['skus'][0]['sku_id'];
        self::assertSame($skuId, (int) $this->repository->mappingBySku($skuId)['link']['sku_id']);
        self::assertSame('10', $this->nativeRedis->hGet('product:stock', (string) $skuId));
        $this->reservations->reserve('ORDER1', [['sku_id' => $skuId, 'quantity' => 3]], 'product:stock');
        $again = $this->import(1, ['A', 'B'], 2);
        self::assertSame($result['product_id'], $again['product_id']);
        self::assertSame(1, $this->connection->table('products')->count());
        self::assertSame(2, $this->connection->table('supply_sku_links')->count());
        self::assertSame('7', $this->nativeRedis->hGet('product:stock', (string) $skuId));
        self::assertSame(7, (int) $this->connection->table('product_skus')->where('id', $skuId)->value('stock'));
    }

    public function testImportAndAppendPreserveNamedSpecificationsAndUseProductNameForBlankSkuTitles(): void
    {
        $specs = [['name' => '规格', 'value' => '默认']];
        $base = ['cost_price' => 80, 'sale_price' => 100, 'stock' => 10, 'spec_values' => $specs];
        $source = ['external_product_id' => 'NAMES', 'name' => '测试', 'main_image' => '', 'available' => true, 'skus' => [
            $base + ['external_sku_id' => 'EMPTY', 'title' => '  '],
            $base + ['external_sku_id' => 'NAMED', 'title' => '原有规格名称'],
            $base + ['external_sku_id' => 'ZERO', 'title' => '0'],
        ]];
        $result = $this->connection->transaction(fn () => $this->products->persist(1, $source, ['category_id' => 1], 1));
        $this->products->project($result);
        $before = $this->catalog->find($result['product_id']);
        self::assertSame(['测试', '原有规格名称', '0'], array_column($before['skus'], 'sku_name'));
        self::assertSame([$specs, $specs, $specs], array_column($before['skus'], 'spec_values'));
        self::assertSame([100, 100, 100], array_column($before['skus'], 'sale_price'));

        $source['skus'][] = $base + ['external_sku_id' => 'ADDED'];
        $synced = $this->connection->transaction(fn () => $this->products->persist(1, $source, [], 2));
        $this->products->project($synced);
        $after = $this->catalog->find($result['product_id']);
        self::assertSame(array_column($before['skus'], 'sku_id'), array_column(\array_slice($after['skus'], 0, 3), 'sku_id'));
        self::assertSame(['测试', '原有规格名称', '0', '测试'], array_column($after['skus'], 'sku_name'));
        foreach ($after['skus'] as $sku) {
            self::assertSame('10', $this->nativeRedis->hGet('product:stock', (string) $sku['sku_id']));
            self::assertSame((int) $sku['sku_id'], (int) $this->repository->mappingBySku((int) $sku['sku_id'])['link']['sku_id']);
        }
    }

    public function testRemovedSkuStopsSellingAndReturningSkuRestoresOnlyItsExistingIdentity(): void
    {
        $result = $this->import(1, ['A', 'B'], 1);
        $before = $this->catalog->find($result['product_id']);
        $skuId = (int) $before['skus'][1]['sku_id'];
        $this->import(1, ['A'], 2);
        self::assertSame('unavailable', $this->repository->mappingBySku($skuId)['link']['status']);
        self::assertSame('0', $this->nativeRedis->hGet('product:stock', (string) $skuId));
        try {
            $this->checkout->assertAvailable('supply_chain', ['sku_id' => $skuId]);
            self::fail('stopped SKU must be rejected');
        } catch (\DomainException) {
            self::assertTrue(true);
        }
        $this->import(1, ['A', 'B'], 3);
        $this->checkout->assertAvailable('supply_chain', ['sku_id' => $skuId]);
        self::assertSame('active', $this->repository->mappingBySku($skuId)['link']['status']);
        self::assertSame('10', $this->nativeRedis->hGet('product:stock', (string) $skuId));
        self::assertSame(array_column($before['skus'], 'sku_id'), array_column($this->catalog->find($result['product_id'])['skus'], 'sku_id'));
    }

    public function testNewUpstreamSkuAddsIdentityWithoutReplacingExistingSku(): void
    {
        $result = $this->import(1, ['A'], 1);
        $before = $this->catalog->find($result['product_id']);
        $this->import(1, ['A', 'NEW'], 2);
        $this->import(1, ['A', 'NEW'], 3);
        $after = $this->catalog->find($result['product_id']);
        self::assertSame('draft', $after['status']);
        self::assertSame($before['skus'][0]['sku_id'], $after['skus'][0]['sku_id']);
        self::assertSame(2, $this->connection->table('product_skus')->count());
        self::assertSame(2, $this->connection->table('supply_skus')->count());
        self::assertSame(2, $this->connection->table('supply_sku_links')->count());
        self::assertSame('NEW', $this->repository->mappingBySku((int) $after['skus'][1]['sku_id'])['sku']['external_sku_id']);
        self::assertSame('10', $this->nativeRedis->hGet('product:stock', (string) $after['skus'][1]['sku_id']));
    }

    public function testStaleProductSnapshotCannotUndoNewerSkuAvailabilityOrProductStatus(): void
    {
        $result = $this->import(1, ['A', 'B'], 1);
        $this->import(1, ['A'], 2);
        $this->import(1, ['A', 'B'], 3);
        $product = $this->catalog->find($result['product_id']);
        $skuId = (int) $product['skus'][1]['sku_id'];
        $old = ['external_product_id' => 'P1', 'name' => '旧名称', 'main_image' => '', 'available' => false, 'skus' => [['external_sku_id' => 'A', 'cost_price' => 1, 'sale_price' => 2, 'stock' => 0]]];
        $stale = $this->connection->transaction(fn () => $this->products->persist(1, $old, [], 2));
        $this->products->project($stale);
        self::assertSame('stale_snapshot', $stale['skipped']);
        self::assertSame([], $stale['projections']);
        self::assertSame('draft', $this->catalog->find($result['product_id'])['status']);
        self::assertSame('available', $this->repository->mappingBySku($skuId)['product']['status']);
        self::assertSame('active', $this->repository->mappingBySku($skuId)['link']['status']);
        self::assertSame('10', $this->nativeRedis->hGet('product:stock', (string) $skuId));
    }

    public function testCheckoutRejectsDuplicateSkuAndChecksTheSelectedConnection(): void
    {
        $result = $this->import(2, ['A'], 1);
        $skuId = (int) $this->catalog->find($result['product_id'])['skus'][0]['sku_id'];
        $address = ['name' => '测试收货人', 'phone' => '13800000000', 'province' => '广东省', 'city' => '深圳市', 'district' => '南山区', 'detail' => '测试路1号'];
        try {
            $this->checkout->groups([['sku_id' => $skuId, 'quantity' => 1], ['sku_id' => $skuId, 'quantity' => 2]], $address, 'normal');
            self::fail('duplicate SKU cannot silently replace frozen quantity');
        } catch (\DomainException $error) {
            self::assertStringContainsString('重复', $error->getMessage());
        }
        $this->repository->update('connections', 2, ['mode' => 'pause_new']);
        $this->expectException(\DomainException::class);
        $this->checkout->assertAvailable('supply_chain', ['connection_id' => 2, 'sku_id' => $skuId]);
    }

    public function testSameSnapshotVersionWithDifferentContentsCannotPartiallyRewriteGoods(): void
    {
        $result = $this->import(1, ['A', 'B'], 5);
        $product = $this->catalog->find($result['product_id']);
        $skuId = (int) $product['skus'][1]['sku_id'];
        $before = $this->repository->mappingBySku($skuId);
        $changed = $before['product']['snapshot'];
        $changed['available'] = false;
        $changed['skus'] = [$changed['skus'][0]];
        try {
            $this->connection->transaction(fn () => $this->products->persist(1, $changed, [], 5));
            self::fail('Conflicting content at the same source version must be rejected before any edits');
        } catch (\DomainException $error) {
            self::assertStringContainsString('相同同步版本', $error->getMessage());
        }
        self::assertSame($before, $this->repository->mappingBySku($skuId));
        self::assertSame('draft', $this->catalog->find($result['product_id'])['status']);
        self::assertSame('10', $this->nativeRedis->hGet('product:stock', (string) $skuId));
    }

    public function testFrozenOrderRoutesTwoPurchasesAndAcknowledgesOnlyOneGroup(): void
    {
        $first = $this->import(1, ['A'], 1);
        $second = $this->import(2, ['B'], 1);
        $skuA = (int) $this->catalog->find($first['product_id'])['skus'][0]['sku_id'];
        $skuB = (int) $this->catalog->find($second['product_id'])['skus'][0]['sku_id'];
        $lines = [['sku_id' => $skuA, 'quantity' => 2], ['sku_id' => $skuB, 'quantity' => 3]];
        $address = ['name' => '测试收货人', 'phone' => '13800000000', 'province' => '广东省', 'city' => '深圳市', 'district' => '南山区', 'detail' => '测试路1号'];
        $groups = $this->checkout->groups($lines, $address, 'normal');
        $this->connection->table('orders')->insert(['id' => 1, 'order_no' => 'GROUPED', 'member_id' => 1, 'status' => 'paid', 'pay_status' => 'paid', 'goods_amount' => 500, 'total_amount' => 500, 'pay_amount' => 500]);
        $this->connection->table('order_addresses')->insert($address + ['order_id' => 1, 'full_address' => '广东省深圳市南山区测试路1号']);
        foreach ($lines as $i => $line) {
            $source = $groups[$i + 1]['items'][$line['sku_id']];
            $item = new OrderItemEntity();
            $item->setSkuId($line['sku_id']);
            $item->setProductId($i === 0 ? $first['product_id'] : $second['product_id']);
            $item->setProductName('测试商品');
            $item->setSkuName('测试规格');
            $item->setQuantity($line['quantity']);
            $item->setUnitPrice(100);
            $item->freezeFulfillment($source['provider'], $source['snapshot']);
            $data = $item->toArray();
            $data['fulfillment_snapshot'] = json_encode($data['fulfillment_snapshot'], \JSON_THROW_ON_ERROR);
            $data['spec_values'] = json_encode($data['spec_values']);
            unset($data['weight']);
            $this->connection->table('order_items')->insert($data + ['id' => $i + 1, 'order_id' => 1]);
        }
        $this->reservations->reserve('GROUPED', $lines, 'product:stock');
        // Existing orders keep their immutable route even if operational mappings change later.
        $this->connection->table('supply_skus')->where('external_sku_id', 'A')->update(['cost_price' => 9999]);
        $this->connection->table('supply_sku_links')->where('sku_id', $skuA)->update(['binding_version' => 20]);
        $read = $this->orders->find(1);
        self::assertSame(1, $read['items'][0]['fulfillment_snapshot']['binding_version']);
        self::assertSame(80, $read['items'][0]['fulfillment_snapshot']['cost_price']);
        self::assertCount(2, $this->connection->transaction(fn () => $this->purchases->ensure(1)));
        $this->connection->transaction(fn () => $this->purchases->ensure(1));
        self::assertSame(2, $this->connection->table('supply_orders')->count());
        self::assertSame(2, $this->connection->table('supply_tasks')->count());
        $purchaseId = (int) $this->connection->table('supply_orders')->where('connection_id', 1)->value('id');
        self::assertSame(80, (int) $this->connection->table('supply_order_items')->where('supply_order_id', $purchaseId)->value('cost_price'));
        $this->purchases->acknowledge($purchaseId);
        $this->purchases->acknowledge($purchaseId);
        self::assertSame('ack:2', $this->nativeRedis->hGet('product:stock', '_external_order:GROUPED:' . $skuA));
        self::assertSame('reserved:3', $this->nativeRedis->hGet('product:stock', '_external_order:GROUPED:' . $skuB));
        $this->reservations->release('GROUPED', $lines, 'product:stock');
        self::assertSame('8', $this->nativeRedis->hGet('product:stock', (string) $skuA));
        self::assertSame('10', $this->nativeRedis->hGet('product:stock', (string) $skuB));
        self::assertSame(8, $this->inventory->reconcile('supply_chain', $skuA, 8, 2, ['GROUPED']));
    }

    public function testRefundOutboxWaitsForProtectionToClearAndThenReleasesExactlyOnce(): void
    {
        $result = $this->import(1, ['A'], 1);
        $skuId = (int) $this->catalog->find($result['product_id'])['skus'][0]['sku_id'];
        $items = [['sku_id' => $skuId, 'quantity' => 2]];
        $this->seedProcurementOrder('DEFERRED', $skuId, 2);
        $this->reservations->reserve('DEFERRED', $items, 'product:stock');
        $this->inventory->protect('supply_chain', 'DEFERRED', $items);
        $outbox = new TradeOutboxRepository();
        $outbox->append('release:DEFERRED', 'stock.release', ['trade_no' => 'DEFERRED', 'items' => $items, 'stock_key' => 'product:stock']);
        $recovery = new AppTradeRecoveryService(
            new OrderSubmissionRepository(),
            $this->createMock(AppOrderSubmissionService::class),
            new OrderPaymentRefundRepository(),
            $this->createMock(AppAfterSaleRefundService::class),
            $outbox,
            $this->reservations,
            $this->createMock(DomainOrderService::class),
            new HyperfTransactionManager(),
            $this->createMock(EventDispatcherInterface::class),
            new NullLogger(),
            $this->createMock(AppPaymentReconciliationService::class),
        );
        $recovery->publish();
        $recovery->publish();
        self::assertNull($this->connection->table('trade_outbox')->value('processed_at'));
        self::assertSame('8', $this->nativeRedis->hGet('product:stock', (string) $skuId));
        $this->inventory->clearProtection('supply_chain', 'DEFERRED', $items);
        self::assertSame('8', $this->nativeRedis->hGet('product:stock', (string) $skuId));
        $recovery->publish();
        $recovery->publish();
        self::assertNotNull($this->connection->table('trade_outbox')->value('processed_at'));
        self::assertSame('10', $this->nativeRedis->hGet('product:stock', (string) $skuId));
        $this->expectException(\DomainException::class);
        $this->inventory->protect('supply_chain', 'DEFERRED', $items);
    }

    public function testCoreProtectionRejectsCommittedAfterSaleBeforePluginEventIsDelivered(): void
    {
        $result = $this->import(1, ['A'], 1);
        $skuId = (int) $this->catalog->find($result['product_id'])['skus'][0]['sku_id'];
        [$orderId, $itemId] = $this->seedProcurementOrder('AFTERSALE', $skuId, 2);
        $items = [['sku_id' => $skuId, 'quantity' => 2]];
        $this->reservations->reserve('AFTERSALE', $items, 'product:stock');
        $saleId = $this->connection->table('order_trade_after_sale')->insertGetId(['after_sale_no' => 'AS1', 'order_id' => $orderId, 'order_item_id' => $itemId, 'member_id' => 1, 'type' => 'refund_only', 'quantity' => 1, 'reason' => '申请退款']);
        self::assertSame(0, $this->connection->table('supply_after_sales')->count(), 'No asynchronous plugin event has been processed.');
        $snapshot = $this->orders->findByNumber('AFTERSALE');
        self::assertSame($saleId, (int) $snapshot['after_sales'][0]['id']);
        self::assertSame($itemId, (int) $snapshot['after_sales'][0]['order_item_id']);
        foreach (['pending_review', 'waiting_refund', 'refunding', 'completed'] as $status) {
            $this->connection->table('order_trade_after_sale')->where('id', $saleId)->update(['status' => $status]);
            try {
                $this->inventory->protect('supply_chain', 'AFTERSALE', $items);
                self::fail('Core aftersale must block purchase');
            } catch (\DomainException $error) {
                self::assertStringContainsString('售后', $error->getMessage());
            }
            self::assertSame('reserved:2', $this->nativeRedis->hGet('product:stock', '_external_order:AFTERSALE:' . $skuId));
        }
        $this->connection->table('order_trade_after_sale')->where('id', $saleId)->update(['status' => 'closed']);
        $this->inventory->protect('supply_chain', 'AFTERSALE', $items);
        self::assertSame('protected:2', $this->nativeRedis->hGet('product:stock', '_external_order:AFTERSALE:' . $skuId));
        $this->inventory->clearProtection('supply_chain', 'AFTERSALE', $items);
        $this->connection->table('order_trade_after_sale')->where('id', $saleId)->update(['refund_status' => 'refunded']);
        $this->expectException(\DomainException::class);
        $this->inventory->protect('supply_chain', 'AFTERSALE', $items);
    }

    public function testCoreProtectionRejectsCancelledRefundedUnpaidAndMismatchedSalesEvenBeforeStockRelease(): void
    {
        $result = $this->import(1, ['A'], 1);
        $skuId = (int) $this->catalog->find($result['product_id'])['skus'][0]['sku_id'];
        [$orderId, $itemId] = $this->seedProcurementOrder('STOPPED', $skuId, 2);
        $items = [['sku_id' => $skuId, 'quantity' => 2]];
        $this->reservations->reserve('STOPPED', $items, 'product:stock');
        foreach ([['pending', 'pending'], ['cancelled', 'paid'], ['refunded', 'refunded'], ['shipped', 'paid'], ['completed', 'paid'], ['paid', 'pending']] as [$status, $paid]) {
            $this->connection->table('orders')->where('id', $orderId)->update(['status' => $status, 'pay_status' => $paid]);
            try {
                $this->inventory->protect('supply_chain', 'STOPPED', $items);
                self::fail('Core sales status must block purchase');
            } catch (\DomainException) {
                self::assertSame('reserved:2', $this->nativeRedis->hGet('product:stock', '_external_order:STOPPED:' . $skuId));
            }
        }
        $this->connection->table('orders')->where('id', $orderId)->update(['status' => 'paid', 'pay_status' => 'paid']);
        $this->connection->table('order_items')->where('id', $itemId)->update(['quantity' => 3]);
        try {
            $this->inventory->protect('supply_chain', 'STOPPED', $items);
            self::fail('Incorrect sales quantity');
        } catch (\DomainException $error) {
            self::assertStringContainsString('数量不一致', $error->getMessage());
        }
        $this->connection->table('order_items')->where('id', $itemId)->update(['quantity' => 2, 'fulfillment_snapshot' => '[]']);
        $this->expectException(\DomainException::class);
        $this->inventory->protect('supply_chain', 'STOPPED', $items);
    }

    public function testRealTradeApplicationsPublishDurableEventsToRealPluginListener(): void
    {
        foreach ([
            '2024_01_01_000006_create_mall_members_table.php' => 'CreateMallMembersTable',
            '2024_01_01_000026_create_mall_order_logs_table.php' => 'CreateMallOrderLogsTable',
            '2024_01_01_000028_create_mall_payments_table.php' => 'CreateMallPaymentsTable',
            '2026_10_15_000001_add_manual_order_payment_method.php' => 'AddManualOrderPaymentMethod',
        ] as $file => $class) {
            require_once BASE_PATH . '/migrations/' . $file;
            (new $class())->up();
        }
        $this->migrate('migrations/2026_03_17_000002_add_reject_reason_to_after_sale_table.php');
        $this->connection->table('members')->insert(['id' => 1, 'nickname' => '测试买家']);
        $result = $this->import(1, ['A'], 1);
        $skuId = (int) $this->catalog->find($result['product_id'])['skus'][0]['sku_id'];
        [$orderId, $itemId] = $this->seedProcurementOrder('REAL-EVENT', $skuId, 2);
        $this->connection->table('orders')->where('id', $orderId)->update(['status' => 'pending', 'pay_status' => 'pending', 'expire_time' => date('Y-m-d H:i:s', time() + 3600)]);
        $settings = $this->createMock(DomainMallSettingService::class);
        $orderRepository = new OrderRepository(new Order());
        $orderDomain = new DomainOrderService($orderRepository, $settings);
        $paymentRepository = new OrderPaymentRepository(new OrderPayment());
        $outbox = new TradeOutboxRepository();
        $gate = $this->createMock(ExternalFulfillmentInterface::class);
        $gate->expects(self::once())->method('assertAvailable')->with('supply_chain', self::callback(static fn ($value) => $value['connection_id'] === 1));
        $payments = new DomainPayService(
            $orderDomain,
            new DomainOrderPaymentService($paymentRepository),
            $paymentRepository,
            $this->createMock(DomainMemberWalletService::class),
            $this->createMock(MemberWalletTransactionRepository::class),
            new PaymentPolicy(),
            new OrderPaymentRefundRepository(),
            $gate
        );
        $manual = new AppOrderManualPaymentService($payments, $orderDomain, new HyperfTransactionManager(), $outbox);
        $input = new OrderManualPayDto();
        $input->order_id = $orderId;
        $input->remark = '测试认款';
        $manual->pay($input);
        $manual->pay($input);
        self::assertSame('paid', $this->connection->table('orders')->where('id', $orderId)->value('pay_status'));
        self::assertSame(1, $this->connection->table('trade_outbox')->where('event_type', 'order.paid')->count());
        self::assertSame(0, $this->connection->table('supply_orders')->count(), 'Payment transaction only records its durable event.');

        $sales = new AfterSaleRepository(new AfterSale());
        $salePort = new AppCommerceAfterSaleService(new DomainCommerceAfterSaleService($sales), $this->createMock(AppAfterSaleCommandService::class));
        $clients = $this->createMock(SupplyChainManagerInterface::class);
        $clients->expects(self::never())->method('forConnection');
        $supplierSales = new AppSupplyAfterSaleService(new DomainSupplyAfterSaleService($this->repository, new DomainSupplyTaskService($this->repository), $salePort, $this->orders), $clients, new HyperfTransactionManager());
        $listener = new SupplyTradeListener(new AppSupplyTradeService(new HyperfTransactionManager(), $this->purchases, $supplierSales));
        $provider = new ListenerProvider();
        foreach ($listener->listen() as $event) {
            $provider->on($event, [$listener, 'process']);
        }
        $attempts = 0;
        $provider->on(OrderPaidIntegrationEvent::class, static function () use (&$attempts): void {
            if (++$attempts === 1) {
                throw new \RuntimeException('Injected failure after the real supplier listener wrote its task');
            }
        }, -100);
        $dispatcher = new EventDispatcher($provider);
        $recovery = new AppTradeRecoveryService(
            new OrderSubmissionRepository(),
            $this->createMock(AppOrderSubmissionService::class),
            new OrderPaymentRefundRepository(),
            $this->createMock(AppAfterSaleRefundService::class),
            $outbox,
            $this->reservations,
            $orderDomain,
            new HyperfTransactionManager(),
            $dispatcher,
            new NullLogger(),
            $this->createMock(AppPaymentReconciliationService::class)
        );
        $recovery->publish();
        self::assertSame(0, $this->connection->table('supply_orders')->count(), 'Listener writes must roll back with the failed acknowledgement.');
        self::assertNull($this->connection->table('trade_outbox')->value('processed_at'));
        $recovery->publish();
        $recovery->publish();
        self::assertSame(1, $this->connection->table('supply_orders')->count());
        self::assertSame(1, $this->connection->table('supply_tasks')->where('type', 'order_push')->count());
        self::assertNotNull($this->connection->table('trade_outbox')->value('processed_at'));
        self::assertSame(2, $attempts);

        $afterSaleApp = new AppApiAfterSaleCommandService(new DomainAfterSaleService($orderRepository, $sales, $settings), $sales, new HyperfTransactionManager(), $outbox);
        $request = $this->createMock(AfterSaleApplyInput::class);
        foreach (['getOrderId' => $orderId, 'getOrderItemId' => $itemId, 'getMemberId' => 1, 'getType' => 'refund_only', 'getReason' => '测试售后', 'getApplyAmount' => 100, 'getQuantity' => 1] as $method => $value) {
            $request->method($method)->willReturn($value);
        }
        $sale = $afterSaleApp->apply($request);
        self::assertSame(1, $this->connection->table('trade_outbox')->where('event_type', 'after_sale.changed')->count());
        self::assertSame(0, $this->connection->table('supply_after_sales')->count());
        $recovery->publish();
        $recovery->publish();
        self::assertSame(1, $this->connection->table('supply_after_sales')->count());
        self::assertSame('pending_review', $this->connection->table('supply_after_sales')->value('status'));
        $afterSaleApp->cancel(1, (int) $sale->id);
        $recovery->publish();
        self::assertSame('closed', $this->connection->table('supply_after_sales')->value('status'));
        self::assertSame(0, $this->connection->table('trade_outbox')->whereNull('processed_at')->count());

        $submissions = new OrderSubmissionRepository();
        $gateway = $this->createMock(PaymentGatewayInterface::class);
        $gateway->expects(self::never())->method('query');
        $gateway->expects(self::never())->method('close');
        $cancellation = new AppOrderCancellationService(
            new DomainOrderCancellationService(
                $orderDomain,
                $this->createMock(CouponServiceInterface::class),
                $gateway,
                $settings,
                $this->createMock(WechatPayConfigResolver::class)
            ),
            $submissions,
            new HyperfTransactionManager(),
            $outbox,
            $this->createMock(SeckillOrderRepository::class),
            $this->createMock(GroupBuyOrderRepository::class),
        );
        $apiOrders = new AppApiOrderCommandService(
            new DomainApiOrderCommandService(
                $orderRepository,
                $orderDomain,
                $this->createMock(OrderTypeStrategyFactory::class),
                $this->createMock(DomainMemberAddressService::class),
                $this->createMock(OrderPendingCacheService::class),
                new DomainExternalFulfillmentService($gate, $this->createMock(ProductSnapshotInterface::class))
            ),
            $settings,
            $this->createMock(AppOrderSubmissionService::class),
            $cancellation,
            $submissions,
            $outbox,
        );
        $this->orders->ship($orderId, 'supply_chain', 'COMPLETE-PKG', ['shipping_company' => 'SF', 'shipping_no' => 'TEST123'], [['order_item_id' => $itemId, 'quantity' => 2]]);
        $apiOrders->confirmReceipt(1, 'REAL-EVENT');
        self::assertSame('completed', $this->connection->table('orders')->where('id', $orderId)->value('status'));
        self::assertSame(1, $this->connection->table('trade_outbox')->where('event_type', 'order.completed')->whereNull('processed_at')->count());
        self::assertSame(0, $this->connection->table('supply_tasks')->where('type', 'order_complete')->count());
        $recovery->publish();
        $recovery->publish();
        self::assertSame(1, $this->connection->table('supply_tasks')->where('type', 'order_complete')->count());

        [$cancelId] = $this->seedProcurementOrder('REAL-CANCEL', $skuId, 2);
        $this->connection->table('orders')->where('id', $cancelId)->update(['status' => 'pending', 'pay_status' => 'pending']);
        $submissions->create('REAL-CANCEL', 1, ['stock_key' => 'product:stock']);
        $this->reservations->reserve('REAL-CANCEL', [['sku_id' => $skuId, 'quantity' => 2]], 'product:stock');
        $cancelled = [];
        $provider->on(OrderCancelledIntegrationEvent::class, static function ($event) use (&$cancelled): void { $cancelled[] = [$event->orderNo, $event->reason]; });
        $apiOrders->cancel(1, 'REAL-CANCEL');
        $apiOrders->cancel(1, 'REAL-CANCEL');
        self::assertSame('cancelled', $this->connection->table('orders')->where('id', $cancelId)->value('status'));
        self::assertSame(1, $this->connection->table('trade_outbox')->where('event_type', 'order.cancelled')->whereNull('processed_at')->count());
        self::assertSame([], $cancelled);
        self::assertSame('8', $this->nativeRedis->hGet('product:stock', (string) $skuId));
        $recovery->publish();
        $recovery->publish();
        self::assertSame([['REAL-CANCEL', '用户取消订单']], $cancelled);
        self::assertSame('10', $this->nativeRedis->hGet('product:stock', (string) $skuId));
        self::assertSame(0, $this->connection->table('supply_orders')->where('order_id', $cancelId)->count(), 'An unpaid cancelled order must never create a supplier purchase.');
        self::assertSame(0, $this->connection->table('trade_outbox')->whereNull('processed_at')->count());
    }

    private function seedProcurementOrder(string $number, int $skuId, int $quantity): array
    {
        $orderId = (int) $this->connection->table('orders')->insertGetId(['order_no' => $number, 'member_id' => 1, 'status' => 'paid', 'pay_status' => 'paid', 'goods_amount' => 200, 'total_amount' => 200, 'pay_amount' => 200]);
        $itemId = (int) $this->connection->table('order_items')->insertGetId(['order_id' => $orderId, 'product_id' => 1, 'sku_id' => $skuId, 'product_name' => '供货商品', 'sku_name' => '规格', 'quantity' => $quantity, 'unit_price' => 100, 'total_price' => 100 * $quantity, 'fulfillment_provider' => 'supply_chain', 'fulfillment_snapshot' => json_encode(['connection_id' => 1, 'external_product_id' => 'P1', 'external_sku_id' => 'A', 'binding_version' => 1, 'cost_price' => 80, 'quantity' => $quantity])]);
        return [$orderId, $itemId];
    }

    private function import(int $connectionId, array $skuNames, int $version): array
    {
        $product = ['external_product_id' => 'P' . $connectionId, 'name' => '真实桥接商品', 'main_image' => '', 'available' => true, 'skus' => array_map(static fn ($sku) => ['external_sku_id' => $sku, 'cost_price' => 80, 'sale_price' => 100, 'stock' => 10], $skuNames)];
        $result = $this->connection->transaction(fn () => $this->products->persist($connectionId, $product, ['category_id' => 1], $version));
        $this->products->project($result);
        return $result;
    }
}
