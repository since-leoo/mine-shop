<?php

declare(strict_types=1);

namespace HyperfTests\Integration\Catalog;

use App\Application\Admin\Catalog\AppProductCommandService;
use App\Application\Admin\Catalog\AppProductQueryService;
use App\Application\Admin\Trade\AppAfterSaleRefundService;
use App\Application\Api\Order\AppOrderSubmissionService;
use App\Application\Api\Order\AppTradeRecoveryService;
use App\Application\Api\Payment\AppPaymentReconciliationService;
use App\Domain\Catalog\Product\Event\ProductCreated;
use App\Domain\Catalog\Product\Event\ProductDeleted;
use App\Domain\Catalog\Product\Event\ProductUpdated;
use App\Domain\Catalog\Product\Listener\ProductSkuStockListener;
use App\Domain\Catalog\Product\Listener\ProductSnapshotListener;
use App\Domain\Catalog\Product\Repository\ProductRepository;
use App\Domain\Catalog\Product\Service\DomainProductService;
use App\Domain\Catalog\Product\Service\DomainProductSnapshotService;
use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\ContentSetting;
use App\Domain\Infrastructure\SystemSetting\ValueObject\ProductSetting;
use App\Domain\Trade\Order\Contract\OrderSubmissionStoreInterface;
use App\Domain\Trade\Order\Contract\StockReservationInterface;
use App\Domain\Trade\Order\Repository\OrderPaymentRefundRepository;
use App\Domain\Trade\Order\Service\DomainOrderService;
use App\Domain\Trade\Shared\Contract\TradeOutboxInterface;
use App\Domain\Trade\Shared\Repository\TradeOutboxRepository;
use App\Infrastructure\Model\Product\Product;
use App\Infrastructure\Service\Trade\HyperfTransactionManager;
use App\Infrastructure\Service\Trade\RedisStockReservation;
use App\Interface\Admin\Dto\Product\ProductDto;
use Hyperf\Config\Config;
use HyperfTests\Support\DatabaseTestCase;
use HyperfTests\Support\IsolatedRedis;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;

final class ProductEventOutboxTest extends DatabaseTestCase
{
    use IsolatedRedis;

    private DomainProductService $products;
    private AppProductCommandService $commands;
    private ProductSkuStockListener $stockListener;
    private RedisStockReservation $stock;
    private EventDispatcherInterface $events;
    private array $dispatched = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([
            '2024_01_01_000004_create_mall_products_table.php' => 'CreateMallProductsTable',
            '2024_01_01_000005_create_mall_product_skus_table.php' => 'CreateMallProductSkusTable',
            '2024_01_01_000008_create_mall_orders_table.php' => 'CreateMallOrdersTable',
            '2024_01_01_000009_create_mall_order_items_table.php' => 'CreateMallOrderItemsTable',
            '2024_01_01_000011_create_mall_order_packages_table.php' => 'CreateMallOrderPackagesTable',
            '2024_01_01_000023_create_mall_product_attributes_table.php' => 'CreateMallProductAttributesTable',
            '2024_01_01_000024_create_mall_product_gallery_table.php' => 'CreateMallProductGalleryTable',
        ] as $file => $class) {
            require_once BASE_PATH . '/migrations/' . $file;
            (new $class())->up();
        }
        $this->migrate('migrations/2026_10_16_000003_add_external_commerce_contracts.php');
        $settings = $this->createMock(DomainMallSettingService::class);
        $settings->method('product')->willReturn(new ProductSetting(true, 10, 0, false, []));
        $settings->method('content')->willReturn(new ContentSetting([], '', '', ''));
        $this->products = new DomainProductService(new ProductRepository(new Product()), $settings);
        $this->commands = $this->commands(new TradeOutboxRepository());
        $this->nativeRedis = $this->openRedis();
        $redis = $this->redisBridge($this->nativeRedis);
        $config = new Config(['cache' => ['default' => ['prefix' => '']]]);
        $this->stockListener = new ProductSkuStockListener($redis, $config);
        $this->stock = new RedisStockReservation($redis, $config);
        $this->dispatchUsing();
    }

    protected function tearDown(): void
    {
        $this->cleanRedis();
        parent::tearDown();
    }

    public function testOriginalProductEventsPersistAndReplayDeltasWithoutResettingReservations(): void
    {
        $product = $this->commands->create($this->input());
        $skuId = $product->skus[0]->id;
        $this->stock->reserve('order', [['sku_id' => $skuId, 'quantity' => 3]], 'product:stock');
        $input = $this->input();
        $input->id = $product->id;
        $input->skus[0]['id'] = $skuId;
        $input->skus[0]['stock'] = 15;
        self::assertTrue($this->commands->update($input));
        self::assertSame('12', $this->nativeRedis->hGet('product:stock', (string) $skuId));
        self::assertSame([ProductCreated::class, ProductUpdated::class], array_column($this->dispatched, 'class'));
        self::assertSame([0, 0], array_column($this->dispatched, 'transaction'));
        self::assertSame(['catalog.product.created', 'catalog.product.updated'], $this->connection->table('trade_outbox')->orderBy('id')->pluck('event_type')->all());
        $update = json_decode($this->connection->table('trade_outbox')->where('event_type', 'catalog.product.updated')->value('payload'), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(5, $update['changes']['stockAdjustments'][0]['delta']);
        $this->recovery()->publish();
        self::assertSame('12', $this->nativeRedis->hGet('product:stock', (string) $skuId));
        self::assertSame(0, $this->connection->table('trade_outbox')->whereNull('processed_at')->count());
        self::assertSame($this->dispatched[0]['id'], $this->dispatched[2]['id']);
        self::assertSame($this->dispatched[1]['id'], $this->dispatched[3]['id']);
        self::assertTrue($this->commands->delete($product->id));
        $this->recovery()->publish();
        self::assertFalse($this->nativeRedis->hExists('product:stock', (string) $skuId));
        self::assertTrue($this->nativeRedis->hExists('product:stock', '_deleted:' . $skuId));
        self::assertSame(ProductDeleted::class, $this->dispatched[4]['class']);
    }

    public function testSnapshotFailureLeavesEventRecoverableAndStockReplayDoesNotIncreaseAvailability(): void
    {
        $fail = true;
        $snapshots = $this->createMock(DomainProductSnapshotService::class);
        $snapshots->expects(self::exactly(3))->method('rememberProduct')->willReturnCallback(static function () use (&$fail): array {
            if ($fail) {
                throw new \RuntimeException('snapshot cache unavailable');
            }
            return [];
        });
        $snapshotListener = new ProductSnapshotListener($snapshots, $this->products, new NullLogger());
        $this->dispatchUsing($snapshotListener);
        $product = $this->commands->create($this->input());
        $skuId = $product->skus[0]->id;
        self::assertSame(1, $this->connection->table('products')->count());
        self::assertSame(1, $this->connection->table('trade_outbox')->whereNull('processed_at')->count());
        $this->stock->reserve('order', [['sku_id' => $skuId, 'quantity' => 3]], 'product:stock');
        $this->recovery()->publish();
        self::assertSame(1, (int) $this->connection->table('trade_outbox')->value('attempts'));
        self::assertStringContainsString('snapshot cache unavailable', $this->connection->table('trade_outbox')->value('last_error'));
        $fail = false;
        $this->recovery()->publish();
        self::assertSame('7', $this->nativeRedis->hGet('product:stock', (string) $skuId));
        self::assertNotNull($this->connection->table('trade_outbox')->value('processed_at'));
    }

    public function testOutboxFailureRollsBackBusinessWritesAndPublishesNothing(): void
    {
        $outbox = $this->createMock(TradeOutboxInterface::class);
        $outbox->method('append')->willReturnCallback(function (string $key, string $type, array $payload): void {
            (new TradeOutboxRepository())->append($key, $type, $payload);
            throw new \RuntimeException('outbox write failed');
        });
        try {
            $this->commands($outbox)->create($this->input());
            self::fail('The transaction must fail');
        } catch (\RuntimeException $error) {
            self::assertSame('outbox write failed', $error->getMessage());
        }
        self::assertSame(0, $this->connection->table('products')->count());
        self::assertSame(0, $this->connection->table('product_skus')->count());
        self::assertSame(0, $this->connection->table('trade_outbox')->count());
        self::assertSame([], $this->dispatched);
        self::assertSame(0, $this->nativeRedis->hLen('product:stock'));
    }

    public function testOuterTransactionRollbackDoesNotPublishUncommittedProduct(): void
    {
        $this->connection->beginTransaction();
        $this->commands->create($this->input());
        self::assertSame([], $this->dispatched);
        $this->connection->rollBack();
        $this->recovery()->publish();
        self::assertSame([], $this->dispatched);
        self::assertSame(0, $this->nativeRedis->hLen('product:stock'));
        self::assertSame(0, $this->connection->table('trade_outbox')->count());
    }

    public function testSupplierSkuEditKeepsExternalStockAndPublishesNoStockAdjustment(): void
    {
        $product = $this->commands->create($this->input());
        $skuId = $product->skus[0]->id;
        $this->connection->table('products')->where('id', $product->id)->update(['fulfillment_provider' => 'supply_chain']);
        $this->nativeRedis->hSet('product:stock', (string) $skuId, 8);
        $input = $this->input();
        $input->id = $product->id;
        $input->skus[0]['id'] = $skuId;
        $input->skus[0]['stock'] = 999;
        $this->commands->update($input);
        $this->recovery()->publish();
        self::assertSame('8', $this->nativeRedis->hGet('product:stock', (string) $skuId));
        self::assertSame(10, (int) $this->connection->table('product_skus')->where('id', $skuId)->value('stock'));
        $payload = json_decode($this->connection->table('trade_outbox')->where('event_type', 'catalog.product.updated')->value('payload'), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame([], $payload['changes']['stockAdjustments']);
    }

    public function testSnapshotReplayReadsCurrentStateAndNeverRestoresDeletedProduct(): void
    {
        $product = $this->commands->create($this->input());
        $created = new ProductCreated($product->id, [$product->skus[0]->id]);
        $this->connection->table('products')->where('id', $product->id)->update(['name' => 'current name']);
        $snapshots = $this->createMock(DomainProductSnapshotService::class);
        $snapshots->expects(self::once())->method('rememberProduct')->with(self::callback(static fn (Product $current): bool => $current->name === 'current name'))->willReturn([]);
        $snapshots->expects(self::once())->method('evictProduct')->with($product->id);
        $snapshots->expects(self::once())->method('deleteSkus')->with([$product->skus[0]->id]);
        $listener = new ProductSnapshotListener($snapshots, $this->products, new NullLogger());
        $listener->process($created);
        $this->commands->delete($product->id);
        $listener->process($created);
    }

    private function input(): ProductDto
    {
        $input = new ProductDto();
        $input->product_code = 'PRODUCT-1';
        $input->name = '商品';
        $input->category_id = 1;
        $input->skus = [['sku_code' => 'SKU-1', 'sale_price' => 100, 'stock' => 10]];
        $input->product_attributes = [];
        return $input;
    }

    private function commands(TradeOutboxInterface $outbox): AppProductCommandService
    {
        return new AppProductCommandService($this->products, new AppProductQueryService($this->products), $outbox, new NullLogger());
    }

    private function dispatchUsing(?ProductSnapshotListener $snapshots = null): void
    {
        $this->events = $this->createMock(EventDispatcherInterface::class);
        $this->events->method('dispatch')->willReturnCallback(function (object $event) use ($snapshots): object {
            if ($event instanceof ProductCreated || $event instanceof ProductUpdated || $event instanceof ProductDeleted) {
                $this->dispatched[] = ['class' => $event::class, 'id' => $event->operationId, 'transaction' => $this->connection->transactionLevel()];
                $this->stockListener->process($event);
                $snapshots?->process($event);
            }
            return $event;
        });
        $this->container->set(EventDispatcherInterface::class, $this->events);
    }

    private function recovery(): AppTradeRecoveryService
    {
        return new AppTradeRecoveryService(
            $this->createMock(OrderSubmissionStoreInterface::class), $this->createMock(AppOrderSubmissionService::class),
            $this->createMock(OrderPaymentRefundRepository::class), $this->createMock(AppAfterSaleRefundService::class),
            new TradeOutboxRepository(), $this->stock, $this->createMock(DomainOrderService::class),
            new HyperfTransactionManager(), $this->events, new NullLogger(), $this->createMock(AppPaymentReconciliationService::class),
        );
    }
}
