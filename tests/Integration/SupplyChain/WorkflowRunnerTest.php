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

use App\Application\Commad\Plugin\AppCommerceInventoryService;
use App\Application\Commad\Plugin\AppCommerceOrderService;
use App\Application\Commad\Plugin\AppCommerceProductService;
use App\Domain\Catalog\Product\Repository\ProductRepository;
use App\Domain\Catalog\Product\Service\DomainProductService;
use App\Domain\Catalog\Product\Service\DomainProductSnapshotService;
use App\Domain\Infrastructure\Plugin\Contract\CommerceAfterSaleInterface;
use App\Domain\Infrastructure\Plugin\Repository\CommerceInventoryRepository;
use App\Domain\Infrastructure\Plugin\Repository\CommerceOrderRepository;
use App\Domain\Infrastructure\Plugin\Repository\CommerceProductRepository;
use App\Domain\Infrastructure\Plugin\Service\DomainCommerceInventoryService;
use App\Domain\Infrastructure\Plugin\Service\DomainCommerceOrderService;
use App\Domain\Infrastructure\Plugin\Service\DomainCommerceProductService;
use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\ContentSetting;
use App\Domain\Infrastructure\SystemSetting\ValueObject\ProductSetting;
use App\Domain\Trade\Order\Entity\OrderItemEntity;
use App\Infrastructure\Model\Product\Product;
use App\Infrastructure\Service\Trade\ExternalInventoryProjection;
use App\Infrastructure\Service\Trade\HyperfTransactionManager;
use App\Infrastructure\Service\Trade\RedisStockReservation;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Driver\RedisDriver;
use Hyperf\Codec\Packer\PhpSerializerPacker;
use Hyperf\Config\Config;
use Hyperf\Context\Context;
use Hyperf\Redis\Redis;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use HyperfTests\Support\DatabaseTestCase;
use HyperfTests\Support\IsolatedRedis;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Plugin\SupplyChain\Application\Commad\AppSupplyAfterSaleService;
use Plugin\SupplyChain\Application\Commad\AppSupplyScheduler;
use Plugin\SupplyChain\Application\Commad\AppSupplyTaskRunner;
use Plugin\SupplyChain\Application\Commad\AppSupplyWorkflow;
use Plugin\SupplyChain\Domain\Repository\SupplyRepository;
use Plugin\SupplyChain\Domain\Service\DomainSupplyAfterSaleService;
use Plugin\SupplyChain\Domain\Service\DomainSupplyCheckoutService;
use Plugin\SupplyChain\Domain\Service\DomainSupplyProductService;
use Plugin\SupplyChain\Domain\Service\DomainSupplyPurchaseService;
use Plugin\SupplyChain\Domain\Service\DomainSupplyTaskService;
use Plugin\SupplyChain\Domain\Service\DomainSupplyWorkflowService;
use Plugin\SupplyChain\Domain\ValueObject\TaskSpecVo;
use Plugin\SupplyChain\Interface\Dto\ImportDto;
use Plugin\SupplyChain\Interfaces\AfterSaleInterface;
use Plugin\SupplyChain\Interfaces\CatalogInterface;
use Plugin\SupplyChain\Interfaces\Data\PurchaseCommand;
use Plugin\SupplyChain\Interfaces\OrderInterface;
use Plugin\SupplyChain\Interfaces\QuoteInterface;
use Plugin\SupplyChain\Interfaces\SelectionInterface;
use Plugin\SupplyChain\Interfaces\SupplyChainClientInterface;
use Plugin\SupplyChain\Interfaces\SupplyChainManagerInterface;
use Psr\Log\NullLogger;

/**
 * Real workflows, database ports, Lua inventory and Redis queues; only supplier HTTP and cache invalidation are faked.
 * @internal
 * @coversNothing
 */
final class WorkflowRunnerTest extends DatabaseTestCase
{
    use IsolatedRedis;

    private SupplyRepository $repository;

    private DomainSupplyTaskService $tasks;

    private DomainSupplyProductService $products;

    private DomainSupplyPurchaseService $purchases;

    private DomainSupplyCheckoutService $checkout;

    private DomainSupplyAfterSaleService $afterSaleDomain;

    private AppSupplyAfterSaleService $afterSales;

    private AppSupplyWorkflow $workflow;

    private AppSupplyTaskRunner $runner;

    private AppSupplyScheduler $scheduler;

    private bool $queueFailure = false;

    private int $protectionClearFailures = 0;

    private int $clientResolutions = 0;

    private AppCommerceProductService $catalog;

    private AppCommerceInventoryService $inventory;

    private AppCommerceOrderService $orders;

    private RedisStockReservation $reservations;

    private MockObject&OrderInterface $supplierOrders;

    private AfterSaleInterface&MockObject $supplierAfterSales;

    private array $queueDrivers = [];

    private array $upstreamProduct;

    private array $network = [];

    private array $customers = [];

    private int $quoteFreight = 0;

    private ?\Closure $onCatalogRead = null;

    private ?\Closure $onProtected = null;

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
        $bridge = \Mockery::mock(Redis::class);
        foreach (['get', 'set', 'zRem', 'publish'] as $method) {
            $bridge->shouldReceive($method)->andReturnUsing(fn (...$args) => $this->nativeRedis->{$method}(...$args));
        }
        $bridge->shouldReceive('eval')->andReturnUsing(function ($script, ...$args) {
            if ($script === ExternalInventoryProjection::CLEAR_PROTECTION_SCRIPT && $this->protectionClearFailures > 0) {
                --$this->protectionClearFailures;
                throw new \RuntimeException('isolated Redis protection cleanup unavailable');
            }
            $result = $this->nativeRedis->eval($script, ...$args);
            if ($script === ExternalInventoryProjection::PROTECT_SCRIPT && $result === 1 && $this->onProtected !== null) {
                ($this->onProtected)();
            }
            return $result;
        });
        $config = new Config(['cache' => ['default' => ['prefix' => '']]]);
        $this->reservations = new RedisStockReservation($bridge, $config);
        $settings = $this->createMock(DomainMallSettingService::class);
        $settings->method('product')->willReturn(new ProductSetting(true, 10, 0, false, []));
        $settings->method('content')->willReturn(new ContentSetting([], '', '', ''));
        $snapshots = $this->createMock(DomainProductSnapshotService::class);
        $transactions = new HyperfTransactionManager();
        $this->catalog = new AppCommerceProductService(new DomainCommerceProductService(new DomainProductService(new ProductRepository(new Product()), $settings), new CommerceProductRepository()), $transactions, $snapshots);
        $this->inventory = new AppCommerceInventoryService(new DomainCommerceInventoryService(new CommerceInventoryRepository(new ExternalInventoryProjection($bridge, $config))), $transactions, $snapshots);
        $this->orders = new AppCommerceOrderService(new DomainCommerceOrderService(new CommerceOrderRepository()), $transactions);
        $this->repository = new SupplyRepository();
        $this->tasks = new DomainSupplyTaskService($this->repository);
        $this->products = new DomainSupplyProductService($this->repository, $this->tasks, $this->catalog, $this->inventory);
        $this->purchases = new DomainSupplyPurchaseService($this->repository, $this->tasks, $this->orders, $this->inventory);
        $this->repository->insert('connections', ['id' => 1, 'name' => '测试供货方', 'api_domain' => 'https://supplier.invalid',
            'app_key' => 'fixture', 'app_secret_ciphertext' => 'fixture-only-unused', 'mode' => 'active']);
        $this->upstreamProduct = $this->product(['600']);
        $supplierCatalog = $this->createMock(CatalogInterface::class);
        $supplierCatalog->method('details')->willReturnCallback(function (array $ids): array {
            $this->recordNetwork('catalog.details', $ids);
            if ($this->onCatalogRead !== null) {
                ($this->onCatalogRead)();
            }
            return [$this->upstreamProduct];
        });
        $selection = $this->createMock(SelectionInterface::class);
        $selection->method('add')->willReturnCallback(fn (array $ids) => $this->recordNetwork('selection.add', $ids));
        $quotes = $this->createMock(QuoteInterface::class);
        $quotes->method('assertAvailable')->willReturnCallback(fn ($command) => $this->recordNetwork('quotes.assertAvailable', []));
        $quotes->method('quote')->willReturnCallback(function ($command): array {
            $this->recordNetwork('quotes.quote', []);
            return ['freight_cents' => $this->quoteFreight, 'checked_at' => time()];
        });
        $this->supplierOrders = $this->createMock(OrderInterface::class);
        $this->supplierAfterSales = $this->createMock(AfterSaleInterface::class);
        $this->supplierAfterSales->method('types')->willReturnCallback(function (): array {
            self::assertSame(0, $this->connection->transactionLevel());
            return [0 => '仅退款'];
        });
        $client = $this->createMock(SupplyChainClientInterface::class);
        $client->method('catalog')->willReturn($supplierCatalog);
        $client->method('selection')->willReturn($selection);
        $client->method('quotes')->willReturn($quotes);
        $client->method('orders')->willReturn($this->supplierOrders);
        $client->method('afterSales')->willReturn($this->supplierAfterSales);
        $manager = $this->createMock(SupplyChainManagerInterface::class);
        $manager->method('forConnection')->with(1)->willReturnCallback(function () use ($client): SupplyChainClientInterface {
            ++$this->clientResolutions;
            return $client;
        });
        $this->checkout = new DomainSupplyCheckoutService($this->repository, $manager);
        $customerAfterSales = $this->createMock(CommerceAfterSaleInterface::class);
        $customerAfterSales->method('find')->willReturnCallback(fn (int $id) => $this->customers[$id] ?? null);
        foreach (['approve', 'reject', 'receive', 'refund'] as $method) {
            $customerAfterSales->expects(self::never())->method($method);
        }
        $this->afterSaleDomain = new DomainSupplyAfterSaleService($this->repository, $this->tasks, $customerAfterSales, $this->orders);
        $this->afterSales = new AppSupplyAfterSaleService($this->afterSaleDomain, $manager, $transactions);
        $domainWorkflow = new DomainSupplyWorkflowService($this->repository, $this->tasks, $this->products, $this->purchases, $this->orders);
        $this->workflow = new AppSupplyWorkflow($transactions, $manager, $this->products, $this->purchases, $domainWorkflow, $this->afterSales);
        $queues = $this->createQueueFactory();
        $this->runner = new AppSupplyTaskRunner($this->tasks, $this->workflow, $transactions, $queues, new NullLogger());
        $this->container->set(AppSupplyTaskRunner::class, $this->runner);
        $this->scheduler = new AppSupplyScheduler($transactions, $domainWorkflow, $this->runner, $bridge);
    }

    protected function tearDown(): void
    {
        try {
            foreach (['task_id', 'attempt_id', 'trace_id'] as $key) {
                Context::destroy('supply_chain.' . $key);
            }
            $this->cleanRedis();
        } finally {
            parent::tearDown();
        }
    }

    public function testQueuedImportProjectsInventoryAndSyncAppendsNewSkuWithoutReplacingIdentity(): void
    {
        $taskId = $this->importTask(1.5);
        self::assertSame(1, $this->runner->dispatch());
        self::assertNotNull($this->repository->require('tasks', $taskId)['last_dispatched_at']);
        $this->consume('supply_catalog');
        $task = $this->repository->require('tasks', $taskId);
        self::assertSame('succeeded', $task['status']);
        $product = $this->catalog->find((int) $task['result']['product_id']);
        self::assertSame('draft', $product['status']);
        self::assertSame('supply_chain', $product['fulfillment_provider']);
        $skuId = (int) $product['skus'][0]['sku_id'];
        self::assertSame(120, (int) $product['skus'][0]['sale_price']);
        self::assertSame('10', $this->nativeRedis->hGet('product:stock', (string) $skuId));
        self::assertSame(10, (int) $this->connection->table('product_skus')->where('id', $skuId)->value('stock'));
        self::assertSame(['selection.add', 'catalog.details'], array_column($this->network, 'operation'));
        self::assertSame($taskId, $this->network[0]['task_id']);
        self::assertSame($task['trace_id'], $this->network[0]['trace_id']);
        self::assertSame(1, (int) $this->repository->query('task_attempts')->where('task_id', $taskId)->value('attempt_no'));
        $this->assertContextCleared();
        $this->runner->run($taskId, 0);
        self::assertCount(2, $this->network, 'A duplicate Redis delivery must not execute the SDK again');

        $this->reservations->reserve('UNPAID', [['sku_id' => $skuId, 'quantity' => 2]], 'product:stock');
        $this->upstreamProduct = $this->product(['600', '601']);
        $sync = $this->connection->transaction(fn () => $this->products->requestSync((int) $task['result']['link_id'], 7));
        $this->runner->run($sync, 0);
        self::assertSame('succeeded', $this->repository->require('tasks', $sync)['status']);
        $refreshed = $this->catalog->find((int) $task['result']['product_id']);
        self::assertSame($skuId, (int) $refreshed['skus'][0]['sku_id']);
        self::assertCount(2, $refreshed['skus']);
        self::assertSame('8', $this->nativeRedis->hGet('product:stock', (string) $skuId));
        self::assertSame(2, $this->repository->query('sku_links')->count());
        self::assertSame(120, (int) $refreshed['skus'][1]['sale_price'], 'New SKUs must inherit the persisted import price policy');
    }

    public function testPaidPurchaseProtectsBeforeHttpThenAcknowledgesOnlyAfterProviderAcceptance(): void
    {
        [$taskId, $purchaseId, $skuId] = $this->paidPurchase();
        $this->supplierOrders->expects(self::once())->method('submit')->willReturnCallback(function (PurchaseCommand $command) use ($purchaseId, $skuId): array {
            $this->recordNetwork('orders.submit', []);
            self::assertSame('protected:2', $this->nativeRedis->hGet('product:stock', '_external_order:ORDER20:' . $skuId));
            self::assertSame('submitting', $this->repository->require('orders', $purchaseId)['status']);
            self::assertSame($this->repository->require('orders', $purchaseId)['merchant_order_no'], $command->merchantOrderNo);
            return ['external_order_numbers' => ['UP1']];
        });
        $this->runner->run($taskId, 0);
        self::assertSame('succeeded', $this->repository->require('tasks', $taskId)['status']);
        self::assertSame('accepted', $this->repository->require('orders', $purchaseId)['status']);
        self::assertSame('ack:2', $this->nativeRedis->hGet('product:stock', '_external_order:ORDER20:' . $skuId));
        self::assertSame('8', $this->nativeRedis->hGet('product:stock', (string) $skuId));
        self::assertSame(1, $this->repository->query('tasks')->where('type', 'order_query')->count());
        $this->assertContextCleared();
    }

    public function testRefundThatWinsDuringPreflightPreventsSupplierSubmission(): void
    {
        [$taskId, $purchaseId, $skuId] = $this->paidPurchase();
        $this->onCatalogRead = function () use ($skuId): void {
            $this->connection->table('orders')->where('id', 20)->update(['status' => 'refunded', 'pay_status' => 'refunded']);
            $this->reservations->release('ORDER20', [['sku_id' => $skuId, 'quantity' => 2]], 'product:stock');
        };
        $this->supplierOrders->expects(self::never())->method('submit');
        $this->runner->run($taskId, 0);
        self::assertSame('failed', $this->repository->require('tasks', $taskId)['status']);
        self::assertSame('10', $this->nativeRedis->hGet('product:stock', (string) $skuId));
        self::assertSame(0, $this->repository->query('tasks')->where('type', 'order_query')->count());
        $this->assertContextCleared();
    }

    public function testUnknownPurchaseIsReconciledWithoutSubmittingAgain(): void
    {
        [$taskId, $purchaseId, $skuId] = $this->paidPurchase();
        $this->supplierOrders->expects(self::once())->method('submit')->willReturnCallback(function (): never {
            $this->recordNetwork('orders.submit', []);
            throw new \RuntimeException('timeout after request write', 10001);
        });
        $this->runner->run($taskId, 0);
        self::assertSame('unknown', $this->repository->require('tasks', $taskId)['status']);
        self::assertSame('unknown', $this->repository->require('orders', $purchaseId)['status']);
        self::assertSame('protected:2', $this->nativeRedis->hGet('product:stock', '_external_order:ORDER20:' . $skuId));
        $this->runner->run($taskId, 0);
        try {
            $this->connection->transaction(fn () => $this->tasks->retry($taskId, 7, 'manual'));
            self::fail('unknown write cannot be retried');
        } catch (\DomainException) {
            self::assertTrue(true);
        }
        $this->supplierOrders->expects(self::once())->method('findByMerchantOrderNo')->willReturnCallback(function (string $number) use ($purchaseId): array {
            $this->recordNetwork('orders.find', []);
            self::assertSame($this->repository->require('orders', $purchaseId)['merchant_order_no'], $number);
            return $this->remoteOrder();
        });
        $this->supplierOrders->expects(self::once())->method('logistics')->willReturnCallback(function (string $number): array {
            $this->recordNetwork('orders.logistics', []);
            return [];
        });
        $queryId = (int) $this->repository->query('tasks')->where('type', 'order_query')->value('id');
        $this->runner->run($queryId, 0);
        self::assertSame('succeeded', $this->repository->require('tasks', $queryId)['status']);
        self::assertSame('succeeded', $this->repository->require('tasks', $taskId)['status']);
        self::assertSame('accepted', $this->repository->require('orders', $purchaseId)['status']);
        self::assertSame('ack:2', $this->nativeRedis->hGet('product:stock', '_external_order:ORDER20:' . $skuId));
        self::assertSame('unknown', $this->repository->decode($this->repository->query('task_attempts')->where('task_id', $taskId)->first())['status'], 'Historical attempt retains the actual timeout outcome');
        $this->assertContextCleared();
    }

    public function testExpiredAfterSaleWriteIsQueuedForQueryAndNeverRepeated(): void
    {
        [$localId, $taskId] = $this->afterSaleTask();
        $claimed = $this->connection->transaction(fn () => $this->tasks->claim($taskId, 0));
        $this->connection->transaction(fn () => $this->afterSaleDomain->start($claimed));
        $this->repository->update('tasks', $taskId, ['lease_expires_at' => date('Y-m-d H:i:s', time() - 1)]);
        $this->supplierAfterSales->expects(self::never())->method('create');
        $this->supplierAfterSales->expects(self::once())->method('findByOrderItem')->with('500')->willReturn(['external_after_sale_id' => 'CANDIDATE1', 'provider_status' => 1]);
        self::assertSame(1, $this->runner->dispatch());
        self::assertSame('unknown', $this->repository->require('tasks', $taskId)['status']);
        self::assertSame(1, $this->repository->query('tasks')->where('type', 'after_sale_query')->count());
        $this->consume('supply_after_sale');
        $row = $this->repository->require('after_sales', $localId);
        self::assertSame('unknown', $row['status']);
        self::assertNull($row['external_after_sale_id']);
        self::assertSame('CANDIDATE1', $row['snapshot']['candidate_external_after_sale_id']);
        $this->assertContextCleared();
    }

    public function testAfterSaleTimeoutCreatesExactlyOneReconciliationTaskAcrossLayers(): void
    {
        [$localId, $taskId] = $this->afterSaleTask();
        $this->supplierAfterSales->expects(self::once())->method('create')->willThrowException(new \RuntimeException('timeout', 10001));
        $this->runner->run($taskId, 0);
        self::assertSame('unknown', $this->repository->require('after_sales', $localId)['status']);
        self::assertSame('unknown', $this->repository->require('tasks', $taskId)['status']);
        self::assertSame(1, $this->repository->query('tasks')->where('type', 'after_sale_query')->count(), 'Domain and runner must share a single durable reconciliation key');
        $this->assertContextCleared();
    }

    public function testPreflightReadTimeoutIsDefinitelyUnsentAndRetriesWithoutQueryingAnOrder(): void
    {
        [$taskId, $purchaseId, $skuId] = $this->paidPurchase();
        $this->onCatalogRead = static fn () => throw new \RuntimeException('catalog timeout', 10001);
        $this->supplierOrders->expects(self::never())->method('submit');
        $this->runner->run($taskId, 0);
        self::assertSame('retry_wait', $this->repository->require('tasks', $taskId)['status']);
        self::assertSame('pending', $this->repository->require('orders', $purchaseId)['status']);
        self::assertSame('reserved:2', $this->nativeRedis->hGet('product:stock', '_external_order:ORDER20:' . $skuId));
        self::assertSame(0, $this->repository->query('tasks')->where('type', 'order_query')->count());
        $this->assertContextCleared();
    }

    public function testAcceptedPurchaseRecoveryRepairsAcknowledgementWithoutNewSubmission(): void
    {
        [$taskId, $purchaseId, $skuId] = $this->paidPurchase();
        $this->inventory->protect('supply_chain', 'ORDER20', [['sku_id' => $skuId, 'quantity' => 2]]);
        $this->repository->update('orders', $purchaseId, ['status' => 'accepted', 'external_orders' => $this->remoteOrder()['orders']]);
        $this->supplierOrders->expects(self::never())->method('submit');
        $beforeNetwork = \count($this->network);
        $this->runner->run($taskId, 0);
        self::assertSame('succeeded', $this->repository->require('tasks', $taskId)['status']);
        self::assertSame($beforeNetwork, \count($this->network), 'Accepted purchase recovery needs no catalog or submit HTTP');
        self::assertSame('ack:2', $this->nativeRedis->hGet('product:stock', '_external_order:ORDER20:' . $skuId));
        self::assertSame(1, $this->repository->query('tasks')->where('type', 'order_query')->count());
    }

    public function testLeaseExpirationAfterInventoryProtectionStopsHttpAndClearsProtection(): void
    {
        [$taskId, $purchaseId, $skuId] = $this->paidPurchase();
        $this->onProtected = fn () => $this->repository->update('tasks', $taskId, ['lease_expires_at' => date('Y-m-d H:i:s', time() - 1)]);
        $this->supplierOrders->expects(self::never())->method('submit');
        $this->runner->run($taskId, 0);
        self::assertSame('retry_wait', $this->repository->require('tasks', $taskId)['status']);
        self::assertSame('pending', $this->repository->require('orders', $purchaseId)['status']);
        self::assertSame('reserved:2', $this->nativeRedis->hGet('product:stock', '_external_order:ORDER20:' . $skuId));
        self::assertSame(0, $this->repository->query('tasks')->where('type', 'order_query')->count());
    }

    public function testExpiredWorkerCannotApplyAProviderResponseAfterItsWriteLeaseEnds(): void
    {
        [$taskId, $purchaseId, $skuId] = $this->paidPurchase();
        $this->supplierOrders->expects(self::once())->method('submit')->willReturnCallback(function () use ($taskId): array {
            $this->recordNetwork('orders.submit', []);
            $this->repository->update('tasks', $taskId, ['lease_expires_at' => date('Y-m-d H:i:s', time() - 1)]);
            return ['external_order_numbers' => ['UP1']];
        });
        $this->runner->run($taskId, 0);
        self::assertNotSame('accepted', $this->repository->require('orders', $purchaseId)['status'], 'A stale worker must reconcile instead of overwriting later purchase details');
        self::assertSame('protected:2', $this->nativeRedis->hGet('product:stock', '_external_order:ORDER20:' . $skuId));
        self::assertSame('unknown', $this->repository->require('tasks', $taskId)['status']);
        self::assertSame(1, $this->repository->query('tasks')->where('type', 'order_query')->count());
    }

    public function testCompleteRequiresCoreCompletionAndLaterQueryPreservesCompletedState(): void
    {
        [$pushId, $purchaseId] = $this->paidPurchase();
        $this->repository->update('tasks', $pushId, ['status' => 'succeeded', 'next_retry_at' => null]);
        $this->repository->update('orders', $purchaseId, ['status' => 'accepted', 'external_orders' => $this->remoteOrder()['orders']]);
        $completeId = $this->connection->transaction(fn () => $this->tasks->enqueue(new TaskSpecVo(1, 'order_complete', 'fixture:complete', ['purchase_id' => $purchaseId])));
        $this->supplierOrders->expects(self::once())->method('receive')->willReturnCallback(function (string $number) use ($purchaseId): void {
            $this->recordNetwork('orders.receive', []);
            self::assertSame($this->repository->require('orders', $purchaseId)['merchant_order_no'], $number);
        });
        $this->runner->run($completeId, 0);
        self::assertSame('failed', $this->repository->require('tasks', $completeId)['status']);
        self::assertSame('accepted', $this->repository->require('orders', $purchaseId)['status']);
        $this->connection->table('orders')->where('id', 20)->update(['status' => 'completed']);
        $this->connection->transaction(fn () => $this->tasks->retry($completeId, 7, '已确认核心订单完成'));
        $this->runner->run($completeId, (int) $this->repository->require('tasks', $completeId)['dispatch_version']);
        self::assertSame('completed', $this->repository->require('orders', $purchaseId)['status']);
        $queryId = $this->connection->transaction(fn () => $this->purchases->queryTask($purchaseId));
        $this->supplierOrders->expects(self::once())->method('findByMerchantOrderNo')->willReturn($this->remoteOrder());
        $this->supplierOrders->expects(self::once())->method('logistics')->willReturn([]);
        $this->runner->run($queryId, 0);
        self::assertSame('succeeded', $this->repository->require('tasks', $queryId)['status']);
        self::assertSame('completed', $this->repository->require('orders', $purchaseId)['status']);
    }

    public function testRedisDispatchFailureRetainsDurableTaskUntilQueueRecovers(): void
    {
        $taskId = $this->importTask();
        $this->queueFailure = true;
        self::assertSame(0, $this->runner->dispatch());
        $task = $this->repository->require('tasks', $taskId);
        self::assertSame('pending', $task['status']);
        self::assertNull($task['last_dispatched_at']);
        self::assertSame(1, $this->repository->query('task_events')->where('task_id', $taskId)->where('action', 'dispatch_failed')->count());
        self::assertSame([], $this->network);
        $this->queueFailure = false;
        self::assertSame(1, $this->runner->dispatch());
        $this->consume('supply_catalog');
        self::assertSame('succeeded', $this->repository->require('tasks', $taskId)['status']);
    }

    public function testSchedulerPersistsAndDispatchesWithoutDoingSupplierHttpAndDeduplicatesSameWindow(): void
    {
        $this->scheduler->tick();
        self::assertSame([], $this->network);
        self::assertSame(2, $this->repository->query('tasks')->count());
        self::assertSame(2, $this->queueDrivers['supply_message']->info()['waiting']);
        self::assertSame('0', $this->nativeRedis->get('supply:recovery:paid_cursor'));
        $this->scheduler->tick();
        self::assertSame(2, $this->repository->query('tasks')->count());
        self::assertSame(2, $this->queueDrivers['supply_message']->info()['waiting']);
        self::assertSame(0, $this->repository->query('task_attempts')->count());
        $this->assertContextCleared();
    }

    #[DataProvider('provideExpiredKnownAfterSaleWriteNeverReplaysAfterRecoveryCases')]
    public function testExpiredKnownAfterSaleWriteNeverReplaysAfterRecovery(string $type): void
    {
        [$localId, $createTask] = $this->afterSaleTask();
        $this->repository->update('tasks', $createTask, ['status' => 'succeeded', 'next_retry_at' => null]);
        $row = $this->repository->require('after_sales', $localId);
        $snapshot = $row['snapshot'];
        $snapshot['return_shipment'] = ['company' => '顺丰速运', 'tracking_no' => 'SF123'];
        $this->repository->update('connections', 1, ['settings' => ['return_carriers' => ['顺丰速运' => ['name' => '顺丰速运', 'code' => 'SF']]]]);
        $this->repository->update('after_sales', $localId, ['external_after_sale_id' => 'AFTER1',
            'status' => $type === 'after_sale_cancel' ? 'cancel_queued' : 'return_queued', 'snapshot' => $snapshot]);
        $taskId = $this->connection->transaction(fn () => $this->tasks->enqueue(new TaskSpecVo(
            1,
            $type,
            'fixture:' . $type,
            ['supply_after_sale_id' => $localId, 'revision' => 1]
        )));
        $claimed = $this->connection->transaction(fn () => $this->tasks->claim($taskId, 0));
        $this->connection->transaction(fn () => $this->afterSaleDomain->start($claimed));
        $this->repository->update('tasks', $taskId, ['lease_expires_at' => date('Y-m-d H:i:s', time() - 1)]);
        $this->supplierAfterSales->expects(self::never())->method('close');
        $this->supplierAfterSales->expects(self::never())->method('shipReturn');
        $this->supplierAfterSales->expects(self::once())->method('find')->with('AFTER1')->willReturn(['external_after_sale_id' => 'AFTER1', 'provider_status' => 1]);
        self::assertSame(1, $this->runner->dispatch());
        self::assertSame('unknown', $this->repository->require('tasks', $taskId)['status']);
        $this->consume('supply_after_sale');
        self::assertSame(1, $this->repository->query('tasks')->where('type', 'after_sale_query')->count());
        self::assertSame('AFTER1', $this->repository->require('after_sales', $localId)['snapshot']['upstream_observation']['external_after_sale_id']);
        self::assertSame('unknown', $this->repository->require('after_sales', $localId)['status'], 'An expired write remains unknown even when a read returns the known supplier id');
    }

    public static function provideExpiredKnownAfterSaleWriteNeverReplaysAfterRecoveryCases(): iterable
    {
        yield ['after_sale_cancel'];
        yield ['after_sale_return'];
    }

    public function testProviderRejectionKeepsItsClassificationWhenProtectionCleanupRedisFails(): void
    {
        [$taskId, $purchaseId, $skuId] = $this->paidPurchase();
        $this->protectionClearFailures = 2;
        $this->supplierOrders->expects(self::once())->method('submit')->willReturnCallback(function (): never {
            $this->recordNetwork('orders.submit', []);
            throw new \DomainException('supplier explicitly rejected procurement');
        });
        $this->runner->run($taskId, 0);
        $source = $this->repository->require('tasks', $taskId);
        self::assertSame('failed', $source['status']);
        self::assertSame('supplier explicitly rejected procurement', $source['last_error']);
        self::assertSame('rejected', $this->repository->require('orders', $purchaseId)['status']);
        self::assertSame('protected:2', $this->nativeRedis->hGet('product:stock', '_external_order:ORDER20:' . $skuId));
        self::assertSame(0, $this->repository->query('tasks')->where('type', 'order_query')->count());
        self::assertSame(1, $this->repository->query('tasks')->where('type', 'order_release_protection')->count());
        $cleanupId = (int) $this->repository->query('tasks')->where('type', 'order_release_protection')->value('id');
        $cleanup = $this->repository->require('tasks', $cleanupId);
        self::assertSame($taskId, $cleanup['payload']['source_task_id']);
        self::assertSame('supplier explicitly rejected procurement', $cleanup['payload']['reason']);
        self::assertSame(1, $this->repository->query('task_events')->where('task_id', $cleanupId)->where('action', 'protection_release_deferred')->count());

        // Local compensation remains available even if operators disable a failing supplier.
        $this->repository->update('connections', 1, ['enabled' => false]);
        $clientResolutions = $this->clientResolutions;
        $networkCount = \count($this->network);
        $this->runner->run($cleanupId, 0);
        $cleanup = $this->repository->require('tasks', $cleanupId);
        self::assertSame('retry_wait', $cleanup['status']);
        self::assertStringContainsString('cleanup unavailable', $cleanup['last_error']);
        self::assertSame($clientResolutions, $this->clientResolutions, 'A local cleanup cannot initialize the supplier SDK');
        self::assertSame($networkCount, \count($this->network));
        $this->repository->update('tasks', $cleanupId, ['next_retry_at' => date('Y-m-d H:i:s', time() - 1)]);
        $this->runner->run($cleanupId, (int) $cleanup['dispatch_version']);
        self::assertSame('succeeded', $this->repository->require('tasks', $cleanupId)['status']);
        self::assertSame('reserved:2', $this->nativeRedis->hGet('product:stock', '_external_order:ORDER20:' . $skuId));
        self::assertSame('failed', $this->repository->require('tasks', $taskId)['status']);
        $this->reservations->release('ORDER20', [['sku_id' => $skuId, 'quantity' => 2]], 'product:stock');
        self::assertSame('10', $this->nativeRedis->hGet('product:stock', (string) $skuId));
        self::assertSame($networkCount, \count($this->network), 'Cleanup and its retries never repeat the procurement POST');
    }

    public function testDefinitelyUnsentPurchaseRemainsRetryableWhenImmediateCleanupFails(): void
    {
        [$taskId, $purchaseId, $skuId] = $this->paidPurchase();
        $this->protectionClearFailures = 1;
        $this->onProtected = fn () => $this->repository->update('tasks', $taskId, ['lease_expires_at' => date('Y-m-d H:i:s', time() - 1)]);
        $this->supplierOrders->expects(self::never())->method('submit');
        $this->runner->run($taskId, 0);
        self::assertSame('retry_wait', $this->repository->require('tasks', $taskId)['status']);
        self::assertStringContainsString('采购尚未发送', $this->repository->require('tasks', $taskId)['last_error']);
        self::assertSame('pending', $this->repository->require('orders', $purchaseId)['status']);
        self::assertSame('protected:2', $this->nativeRedis->hGet('product:stock', '_external_order:ORDER20:' . $skuId));
        $cleanupId = (int) $this->repository->query('tasks')->where('type', 'order_release_protection')->value('id');
        $this->runner->run($cleanupId, 0);
        self::assertSame('succeeded', $this->repository->require('tasks', $cleanupId)['status']);
        self::assertSame('reserved:2', $this->nativeRedis->hGet('product:stock', '_external_order:ORDER20:' . $skuId));
        self::assertSame(0, $this->repository->query('tasks')->where('type', 'order_query')->count());
    }

    #[DataProvider('provideLateCleanupDoesNotClearProtectionOwnedByANewerPurchaseCases')]
    public function testLateCleanupDoesNotClearProtectionOwnedByANewerPurchase(string $state): void
    {
        [$taskId, $purchaseId, $skuId] = $this->paidPurchase();
        $this->protectionClearFailures = 1;
        $this->supplierOrders->expects(self::once())->method('submit')->willThrowException(new \DomainException('known rejection'));
        $this->runner->run($taskId, 0);
        $cleanupId = (int) $this->repository->query('tasks')->where('type', 'order_release_protection')->value('id');
        // A later operator-authorized attempt takes the same purchase lock before protect().
        $this->connection->transaction(fn () => $this->purchases->submitting($purchaseId));
        $this->purchases->protect($purchaseId);
        if ($state !== 'submitting') {
            $this->repository->update('orders', $purchaseId, ['status' => $state]);
        }
        $clientResolutions = $this->clientResolutions;
        $this->runner->run($cleanupId, 0);
        $cleanup = $this->repository->require('tasks', $cleanupId);
        self::assertSame('succeeded', $cleanup['status']);
        self::assertSame('purchase_state_' . $state, $cleanup['result']['skipped']);
        self::assertSame('protected:2', $this->nativeRedis->hGet('product:stock', '_external_order:ORDER20:' . $skuId));
        self::assertSame($clientResolutions, $this->clientResolutions);
        self::assertSame(1, $this->repository->query('task_events')->where('task_id', $cleanupId)->where('action', 'protection_release_skipped')->count());
    }

    public static function provideLateCleanupDoesNotClearProtectionOwnedByANewerPurchaseCases(): iterable
    {
        yield ['submitting'];
        yield ['unknown'];
        yield ['accepted'];
        yield ['completed'];
    }

    public function testPurchaseFailureAndProtectionCompensationShareOneTransaction(): void
    {
        [$taskId, $purchaseId] = $this->paidPurchase();
        $task = $this->connection->transaction(fn () => $this->tasks->claim($taskId, 0));
        $this->connection->transaction(fn () => $this->purchases->submitting($purchaseId));
        try {
            $this->connection->transaction(function () use ($task, $purchaseId): void {
                $this->purchases->failure($purchaseId, new \DomainException('known rejection'), $task);
                throw new \RuntimeException('simulate local commit failure');
            });
            self::fail('Fixture transaction should roll back');
        } catch (\RuntimeException $error) {
            self::assertSame('simulate local commit failure', $error->getMessage());
        }
        self::assertSame('submitting', $this->repository->require('orders', $purchaseId)['status']);
        self::assertSame(0, $this->repository->query('tasks')->where('type', 'order_release_protection')->count());
        self::assertSame(0, $this->repository->query('task_events')->where('task_id', $taskId)->where('action', 'protection_release_queued')->count());
    }

    private function createQueueFactory(): DriverFactory
    {
        $proxy = \Mockery::mock(RedisProxy::class);
        foreach (['lPush', 'brPop', 'zAdd', 'zRem', 'zrevrangebyscore', 'lLen', 'zCard', 'del', 'rpoplpush'] as $method) {
            $proxy->shouldReceive($method)->andReturnUsing(function (...$args) use ($method) {
                if ($method === 'lPush' && $this->queueFailure) {
                    throw new \RuntimeException('isolated test queue temporarily unavailable');
                }
                return $this->nativeRedis->{$method}(...$args);
            });
        }
        $factory = $this->createMock(RedisFactory::class);
        $factory->method('get')->with('default')->willReturn($proxy);
        $this->container->set(RedisFactory::class, $factory);
        $this->container->set(PhpSerializerPacker::class, new PhpSerializerPacker());
        foreach (['supply_catalog', 'supply_order', 'supply_after_sale', 'supply_message'] as $name) {
            $this->queueDrivers[$name] = new RedisDriver($this->container, ['channel' => '{' . $name . '-queue}', 'timeout' => 1, 'handle_timeout' => 180]);
        }
        $queues = $this->createMock(DriverFactory::class);
        $queues->method('get')->willReturnCallback(fn (string $name) => $this->queueDrivers[$name]);
        return $queues;
    }

    private function consume(string $queue): void
    {
        [$packed, $message] = $this->queueDrivers[$queue]->pop();
        self::assertNotFalse($packed, 'Expected an actual serialized task in the isolated Redis queue');
        $message->job()->handle();
        self::assertTrue($this->queueDrivers[$queue]->ack($packed));
    }

    private function importTask(float $multiplier = 1): int
    {
        return $this->connection->transaction(fn () => $this->products->requestImports(new ImportDto([
            'connection_id' => 1, 'external_product_ids' => ['700'], 'category_id' => 1, 'price_multiplier' => $multiplier,
        ]), 7))['task_ids'][0];
    }

    private function paidPurchase(): array
    {
        $import = $this->importTask();
        $this->runner->run($import, 0);
        $result = $this->repository->require('tasks', $import)['result'];
        $product = $this->catalog->find((int) $result['product_id']);
        $skuId = (int) $product['skus'][0]['sku_id'];
        $address = ['name' => '测试收货人', 'phone' => '13800000000', 'province' => '广东省', 'city' => '深圳市', 'district' => '南山区', 'detail' => '测试路1号'];
        $groups = $this->checkout->groups([['sku_id' => $skuId, 'quantity' => 2]], $address, 'normal');
        $source = $groups[1]['items'][$skuId];
        $this->connection->table('orders')->insert(['id' => 20, 'order_no' => 'ORDER20', 'member_id' => 1, 'status' => 'paid', 'pay_status' => 'paid', 'goods_amount' => 200, 'total_amount' => 200, 'pay_amount' => 200]);
        $this->connection->table('order_addresses')->insert($address + ['order_id' => 20, 'full_address' => '广东省深圳市南山区测试路1号']);
        $item = new OrderItemEntity();
        $item->setSkuId($skuId);
        $item->setProductId((int) $result['product_id']);
        $item->setProductName('测试商品');
        $item->setSkuName('测试规格');
        $item->setQuantity(2);
        $item->setUnitPrice(100);
        $item->freezeFulfillment($source['provider'], $source['snapshot']);
        $data = $item->toArray();
        $data['fulfillment_snapshot'] = json_encode($data['fulfillment_snapshot'], \JSON_THROW_ON_ERROR);
        $data['spec_values'] = json_encode($data['spec_values'], \JSON_THROW_ON_ERROR);
        unset($data['weight']);
        $this->connection->table('order_items')->insert($data + ['id' => 30, 'order_id' => 20]);
        $this->reservations->reserve('ORDER20', [['sku_id' => $skuId, 'quantity' => 2]], 'product:stock');
        $taskId = $this->connection->transaction(fn () => $this->purchases->ensure(20))[0];
        $purchaseId = (int) $this->repository->query('orders')->where('order_id', 20)->value('id');
        return [$taskId, $purchaseId, $skuId];
    }

    private function afterSaleTask(): array
    {
        [$push, $purchase] = $this->paidPurchase();
        // Accepted procurement fixture; its HTTP was outside this after-sale recovery scenario.
        $this->repository->update('tasks', $push, ['status' => 'succeeded', 'next_retry_at' => null]);
        $this->repository->update('orders', $purchase, ['status' => 'accepted', 'external_orders' => $this->remoteOrder()['orders']]);
        $this->customers[10] = ['id' => 10, 'order_id' => 20, 'order_item_id' => 30, 'quantity' => 2,
            'apply_amount' => 200, 'refund_amount' => 0, 'status' => 'pending_review', 'images' => [],
            'order_item' => ['fulfillment_provider' => 'supply_chain']];
        $this->afterSales->changed(10, 'apply');
        $localId = (int) $this->repository->query('after_sales')->where('after_sale_id', 10)->value('id');
        $input = ['external_order_item_id' => '500', 'quantity' => 1, 'amount_cents' => 80, 'freight_cents' => 0,
            'technical_service_fee_cents' => 0, 'reason_type' => 1, 'reason' => '申请退款', 'description' => '',
            'received_status' => 0, 'refund_type' => 0, 'refund_way' => 1];
        $task = $this->afterSales->submit($localId, $input, 7);
        return [$localId, $task['task_id']];
    }

    private function product(array $skuIds): array
    {
        return ['external_product_id' => '700', 'name' => '编排测试商品', 'main_image' => '', 'available' => true,
            'skus' => array_map(static fn (string $id) => ['external_sku_id' => $id, 'cost_price' => 80, 'sale_price' => 100,
                'stock' => 10, 'spec_values' => ['规格' => $id]], $skuIds)];
    }

    private function remoteOrder(): array
    {
        return ['orders' => [['external_order_no' => 'UP1', 'freight_cents' => 0, 'items' => [[
            'external_order_item_id' => '500', 'external_sku_id' => '600', 'quantity' => 2,
            'amount_cents' => 160, 'technical_service_fee_cents' => 0,
        ]]]]];
    }

    private function recordNetwork(string $operation, array $parameters): void
    {
        self::assertSame(0, $this->connection->transactionLevel(), $operation . ' must not run inside a transaction');
        $taskId = Context::get('supply_chain.task_id');
        $attemptId = Context::get('supply_chain.attempt_id');
        self::assertIsInt($taskId);
        self::assertIsInt($attemptId);
        self::assertSame('running', $this->repository->require('tasks', $taskId)['status']);
        self::assertSame('running', $this->repository->require('task_attempts', $attemptId)['status']);
        $this->network[] = ['operation' => $operation, 'parameters' => $parameters, 'task_id' => $taskId,
            'attempt_id' => $attemptId, 'trace_id' => Context::get('supply_chain.trace_id')];
    }

    private function assertContextCleared(): void
    {
        foreach (['task_id', 'attempt_id', 'trace_id'] as $key) {
            self::assertNull(Context::get('supply_chain.' . $key));
        }
    }
}
