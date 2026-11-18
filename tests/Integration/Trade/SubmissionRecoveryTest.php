<?php

declare(strict_types=1);

namespace HyperfTests\Integration\Trade;

use App\Application\Api\Order\AppOrderSubmissionService;
use App\Application\Api\Order\AppApiOrderCommandService;
use App\Application\Api\Order\AppOrderCancellationService;
use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\PaymentSetting;
use App\Domain\Trade\Order\Api\Command\DomainApiOrderCommandService;
use App\Domain\Trade\Order\Job\OrderCreateJob;
use App\Domain\Trade\Order\Contract\OrderJobQueueInterface;
use App\Domain\Trade\Order\Contract\StockReservationInterface;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Repository\OrderRepository;
use App\Domain\Trade\Order\Repository\OrderSubmissionRepository;
use App\Domain\Trade\Order\Service\DomainOrderCreationService;
use App\Domain\Trade\Shared\Repository\TradeOutboxRepository;
use App\Infrastructure\Service\Trade\HyperfTransactionManager;
use HyperfTests\Support\DatabaseTestCase;
use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;

final class SubmissionRecoveryTest extends DatabaseTestCase
{
    private OrderSubmissionRepository $store;

    protected function setUp(): void
    {
        parent::setUp();
        require_once BASE_PATH . '/migrations/2026_03_16_000001_create_after_sale_tables.php';
        (new \CreateAfterSaleTables())->up();
        $this->store = new OrderSubmissionRepository();
        foreach ([
            '2024_01_01_000004_create_mall_products_table.php' => 'CreateMallProductsTable',
            '2024_01_01_000008_create_mall_orders_table.php' => 'CreateMallOrdersTable',
            '2024_01_01_000009_create_mall_order_items_table.php' => 'CreateMallOrderItemsTable',
            '2024_01_01_000010_create_mall_order_addresses_table.php' => 'CreateMallOrderAddressesTable',
            '2024_01_01_000011_create_mall_order_packages_table.php' => 'CreateMallOrderPackagesTable',
            '2024_01_01_000015_create_mall_coupons_table.php' => 'CreateMallCouponsTable',
            '2024_01_01_000016_create_mall_coupon_users_table.php' => 'CreateMallCouponUsersTable',
        ] as $file => $class) {
            require_once BASE_PATH . '/migrations/' . $file;
            (new $class())->up();
        }
        $this->migrate('migrations/2026_10_16_000003_add_external_commerce_contracts.php');

    }

    public function testFailedJobsPersistOnlySafeErrorsAndKeepFullExceptionsInLogs(): void
    {
        $logged = [];
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::exactly(5))->method('error')->willReturnCallback(static function (string $message, array $context) use (&$logged): void {
            self::assertSame('订单创建失败', $message);
            $logged[$context['trade_no']] = $context['exception'];
        });
        $orders = $this->createMock(OrderRepository::class);
        $orders->method('findExistingByOrderNo')->willReturn(null);
        $service = new AppOrderSubmissionService($this->store,
            $this->createMock(StockReservationInterface::class),
            $this->createMock(OrderJobQueueInterface::class), $orders,
            $this->createMock(DomainOrderCreationService::class), new HyperfTransactionManager(),
            new TradeOutboxRepository(), $logger);
        $this->container->set(AppOrderSubmissionService::class, $service);
        $polling = $this->pollingService($service);
        $cases = [
            [new \DomainException('库存不足'), '库存不足'],
            [new \InvalidArgumentException('商品信息无效'), '商品信息无效'],
            [new \PDOException('SQLSTATE password=private-db-secret'), '订单创建失败，请稍后重试'],
            [new \RuntimeException('SDK token=private-provider-secret'), '订单创建失败，请稍后重试'],
            [new class('vendor secret') extends \InvalidArgumentException {}, '订单创建失败，请稍后重试'],
        ];
        foreach ($cases as $index => [$error, $expected]) {
            $tradeNo = 'SAFE-ERROR-' . $index;
            $this->store->create($tradeNo, 7, ['items' => [], 'stock_key' => 'product:stock']);
            (new OrderCreateJob($tradeNo))->fail($error);
            self::assertSame($error, $logged[$tradeNo]);
            self::assertSame(AppOrderSubmissionService::PUBLIC_ERROR_PREFIX . $expected, $this->store->find($tradeNo)['last_error']);
            self::assertSame('failed', $polling->getSubmitResult($tradeNo, 7)['status']);
            self::assertSame($expected, $polling->getSubmitResult($tradeNo, 7)['error']);
        }
        self::assertSame(5, $this->connection->table('trade_outbox')->where('event_type', 'stock.release')->count());
    }

    public function testHistoricalFailedDiagnosticsAreHiddenWithoutChangingStoredRecords(): void
    {
        $polling = $this->pollingService($this->createMock(AppOrderSubmissionService::class));
        $this->store->create('LEGACY-ERROR', 7, ['items' => [], 'stock_key' => 'product:stock']);
        foreach (['SQLSTATE credential=old-private-secret', '库存不足', '', 'public:v2:unrecognized-format'] as $error) {
            $this->store->updateStatus('LEGACY-ERROR', 'failed', $error);
            $result = $polling->getSubmitResult('LEGACY-ERROR', 7);
            self::assertSame('failed', $result['status']);
            self::assertSame('订单创建失败，请稍后重试', $result['error']);
            self::assertSame($error, $this->store->find('LEGACY-ERROR')['last_error']);
        }
    }

    public function testPollingDoesNotExposeRecoveryDiagnosticsAndStillChecksMembership(): void
    {
        $polling = $this->pollingService($this->createMock(AppOrderSubmissionService::class));
        $this->store->create('RECOVERY', 7, ['items' => [], 'stock_key' => 'product:stock']);
        foreach (['pending', 'reserved', 'created'] as $status) {
            $this->store->updateStatus('RECOVERY', $status, 'SQLSTATE credential=private-secret');
            $result = $polling->getSubmitResult('RECOVERY', 7);
            self::assertSame($status === 'created' ? 'created' : 'processing', $result['status']);
            self::assertSame('', $result['error']);
        }
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('订单提交不存在');
        $polling->getSubmitResult('RECOVERY', 8);
    }

    private function pollingService(AppOrderSubmissionService $submissions): AppApiOrderCommandService
    {
        $settings = $this->createMock(DomainMallSettingService::class);
        $settings->method('payment')->willReturn(new PaymentSetting(false, [], true, 0, true, []));
        return new AppApiOrderCommandService(
            $this->createMock(DomainApiOrderCommandService::class), $settings, $submissions,
            $this->createMock(AppOrderCancellationService::class), $this->store, new TradeOutboxRepository(),
        );
    }

    public function testQueueFailureRemainsRecoverableAndRepeatedConsumptionCreatesOnce(): void
    {
        $stock = $this->createMock(StockReservationInterface::class);
        $stock->expects(self::exactly(2))->method('reserve');
        $queue = $this->createMock(OrderJobQueueInterface::class);
        $queue->method('push')->willThrowException(new \RuntimeException('queue offline'));
        $orders = $this->createMock(OrderRepository::class);
        $orders->method('findExistingByOrderNo')->willReturn(null);
        $creation = $this->createMock(DomainOrderCreationService::class);
        $creation->expects(self::once())->method('create')->willReturn(new OrderEntity());
        $service = new AppOrderSubmissionService($this->store, $stock, $queue, $orders, $creation, new HyperfTransactionManager(), new TradeOutboxRepository(), new NullLogger());
        $service->submit('A', 7, ['items' => [['sku_id' => 1, 'quantity' => 2]], 'stock_key' => 'product:stock', 'snapshot' => []]);
        self::assertSame('reserved', $this->store->find('A')['status']);
        $service->process('A');
        $service->process('A');
        $service->fail('A', new \RuntimeException('late queue failure'));
        self::assertSame('created', $this->store->find('A')['status']);
        self::assertSame(1, $this->connection->table('trade_outbox')->count());
        self::assertSame('order.created', $this->connection->table('trade_outbox')->value('event_type'));
    }

    public function testFailedConsumptionAndRepeatedCompensationQueueOneRelease(): void
    {
        $stock = $this->createMock(StockReservationInterface::class);
        $queue = $this->createMock(OrderJobQueueInterface::class);
        $queue->method('push')->willReturn(true);
        $orders = $this->createMock(OrderRepository::class);
        $orders->method('findExistingByOrderNo')->willReturn(null);
        $creation = $this->createMock(DomainOrderCreationService::class);
        $creation->method('create')->willThrowException(new \RuntimeException('database failure'));
        $service = new AppOrderSubmissionService($this->store, $stock, $queue, $orders, $creation, new HyperfTransactionManager(), new TradeOutboxRepository(), new NullLogger());
        $service->submit('A', 7, ['items' => [['sku_id' => 1, 'quantity' => 2]], 'stock_key' => 'product:stock', 'snapshot' => []]);
        try {
            $service->process('A');
            self::fail('failure expected');
        } catch (\RuntimeException) {
            self::assertSame('reserved', $this->store->find('A')['status']);
        }
        $service->fail('A', new \RuntimeException('database failure'));
        $service->fail('A', new \RuntimeException('database failure'));
        self::assertSame('failed', $this->store->find('A')['status']);
        self::assertSame(1, $this->connection->table('trade_outbox')->where('event_type', 'stock.release')->count());
    }
    public function testReservationTransportFailureKeepsAcceptedSubmissionRecoverable(): void
    {
        $stock = $this->createMock(StockReservationInterface::class);
        $attempt = 0;
        $stock->method('reserve')->willReturnCallback(function () use (&$attempt): void {
            if (++$attempt === 1) { throw new \RuntimeException('redis response lost'); }
        });
        $queue = $this->createMock(OrderJobQueueInterface::class);
        $queue->method('push')->willReturn(false);
        $orders = $this->createMock(OrderRepository::class);
        $orders->method('findExistingByOrderNo')->willReturn(null);
        $creation = $this->createMock(DomainOrderCreationService::class);
        $creation->expects(self::once())->method('create')->willReturn(new OrderEntity());
        $service = new AppOrderSubmissionService($this->store, $stock, $queue, $orders, $creation, new HyperfTransactionManager(), new TradeOutboxRepository(), new NullLogger());
        $service->submit('TIMEOUT', 7, ['items' => [['sku_id' => 1, 'quantity' => 2]], 'stock_key' => 'product:stock', 'snapshot' => []]);
        self::assertSame('pending', $this->store->find('TIMEOUT')['status']);
        $service->process('TIMEOUT');
        self::assertSame('created', $this->store->find('TIMEOUT')['status']);
        self::assertSame(2, $attempt);
    }

    public function testActualOrderItemsAddressCouponAndOutboxCommitOnceAfterPostCreateFailure(): void
    {
        $couponId = $this->connection->table('coupons')->insertGetId([
            'name' => 'test', 'type' => 'fixed', 'value' => 100, 'total_quantity' => 10,
            'start_time' => date('Y-m-d H:i:s', time() - 60), 'end_time' => date('Y-m-d H:i:s', time() + 600),
        ]);
        $couponUserId = $this->connection->table('coupon_users')->insertGetId([
            'coupon_id' => $couponId, 'member_id' => 7, 'status' => 'unused',
            'received_at' => date('Y-m-d H:i:s'), 'expire_at' => date('Y-m-d H:i:s', time() + 600),
        ]);
        $coupons = new \App\Domain\Trade\Coupon\Service\CouponServiceAdapter(
            new \App\Domain\Trade\Coupon\Repository\CouponUserRepository(new \App\Infrastructure\Model\Coupon\CouponUser()),
            new \App\Domain\Trade\Coupon\Repository\CouponRepository(new \App\Infrastructure\Model\Coupon\Coupon()));
        $stock = $this->createMock(StockReservationInterface::class);
        $queue = $this->createMock(OrderJobQueueInterface::class);
        $queue->method('push')->willReturn(false);
        $orders = new OrderRepository(new \App\Infrastructure\Model\Order\Order());
        $normal = new \App\Domain\Trade\Order\Strategy\NormalOrderStrategy(
            $this->createMock(\App\Domain\Catalog\Product\Contract\ProductSnapshotInterface::class), $coupons,
            $this->createMock(\App\Domain\Trade\Order\Contract\FreightServiceInterface::class));
        $settings = $this->createMock(\App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService::class);
        $settings->method('order')->willReturn(new \App\Domain\Infrastructure\SystemSetting\ValueObject\OrderSetting(30, 7, 7, false, '', ''));
        $creation = new DomainOrderCreationService($orders, new \App\Domain\Trade\Order\Factory\OrderTypeStrategyFactory([$normal]), $settings, $this->container);
        $broken = $this->createMock(\App\Domain\Trade\Shared\Contract\TradeOutboxInterface::class);
        $broken->method('append')->willThrowException(new \RuntimeException('outbox unavailable after order and coupon writes'));
        $service = new AppOrderSubmissionService($this->store, $stock, $queue, $orders, $creation, new HyperfTransactionManager(), $broken, new NullLogger());
        $payload = [
            'snapshot' => ['member_id' => 7, 'order_type' => 'normal', 'goods_amount' => 600, 'shipping_fee' => 0,
                'discount_amount' => 100, 'total_amount' => 500, 'pay_amount' => 500, 'coupon_amount' => 100],
            'items' => [['sku_id' => 1, 'product_id' => 1, 'product_name' => 'test', 'sku_name' => 'test sku', 'unit_price' => 300, 'quantity' => 2]],
            'address' => ['name' => 'tester', 'phone' => '13800000000', 'province' => 'test', 'city' => 'test', 'district' => 'test', 'detail' => 'test'],
            'coupon_user_ids' => [$couponUserId], 'stock_key' => 'product:stock',
        ];
        $service->submit('REAL', 7, $payload);
        try {
            $service->process('REAL');
            self::fail('outbox exception expected');
        } catch (\RuntimeException $e) {
            self::assertSame('outbox unavailable after order and coupon writes', $e->getMessage());
        }
        self::assertSame(0, $this->connection->table('orders')->count());
        self::assertSame(0, $this->connection->table('order_items')->count());
        self::assertSame('unused', $this->connection->table('coupon_users')->value('status'));
        self::assertSame('reserved', $this->store->find('REAL')['status']);
        $service = new AppOrderSubmissionService($this->store, $stock, $queue, $orders, $creation, new HyperfTransactionManager(), new TradeOutboxRepository(), new NullLogger());
        $service->process('REAL');
        $service->process('REAL');
        self::assertSame(1, $this->connection->table('orders')->count());
        self::assertSame(1, $this->connection->table('order_items')->count());
        self::assertSame(1, $this->connection->table('order_addresses')->count());
        self::assertSame('used', $this->connection->table('coupon_users')->value('status'));
        self::assertSame(1, (int) $this->connection->table('coupons')->value('used_quantity'));
        self::assertSame(1, $this->connection->table('trade_outbox')->count());
        $orderId = (int) $this->connection->table('orders')->value('id');
        (new HyperfTransactionManager())->run(fn () => $coupons->releaseOrderCoupons($orderId));
        (new HyperfTransactionManager())->run(fn () => $coupons->releaseOrderCoupons($orderId));
        self::assertSame('unused', $this->connection->table('coupon_users')->value('status'));
        self::assertNull($this->connection->table('coupon_users')->value('order_id'));
        self::assertSame(0, (int) $this->connection->table('coupons')->value('used_quantity'));
    }

}
