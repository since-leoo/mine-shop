<?php

declare(strict_types=1);

namespace HyperfTests\Integration\Trade;

use App\Application\Admin\Trade\AppOrderManualPaymentService;
use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Member\Repository\MemberWalletRepository;
use App\Domain\Member\Repository\MemberWalletTransactionRepository;
use App\Domain\Member\Service\DomainMemberWalletService;
use App\Domain\Trade\Order\Repository\OrderPaymentRefundRepository;
use App\Domain\Trade\Order\Repository\OrderPaymentRepository;
use App\Domain\Trade\Order\Repository\OrderRepository;
use App\Domain\Trade\Order\Service\DomainOrderPaymentService;
use App\Domain\Trade\Order\Service\DomainOrderService;
use App\Domain\Trade\Payment\DomainPayService;
use App\Domain\Trade\Payment\ValueObject\PaymentPolicy;
use App\Domain\Trade\Shared\Contract\TradeOutboxInterface;
use App\Domain\Trade\Shared\Repository\TradeOutboxRepository;
use App\Infrastructure\Model\Member\MemberWallet;
use App\Infrastructure\Model\Member\MemberWalletTransaction;
use App\Infrastructure\Model\Order\Order;
use App\Infrastructure\Model\Order\OrderPayment;
use App\Infrastructure\Service\Trade\HyperfTransactionManager;
use App\Interface\Admin\Dto\Order\OrderManualPayDto;
use HyperfTests\Support\DatabaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class ManualOrderPaymentTest extends DatabaseTestCase
{
    private DomainOrderService $orders;

    private DomainPayService $payments;

    protected function setUp(): void
    {
        parent::setUp();
        $migrations = [
            '2024_01_01_000005_create_mall_product_skus_table.php' => 'CreateMallProductSkusTable',
            '2024_01_01_000006_create_mall_members_table.php' => 'CreateMallMembersTable',
            '2024_01_01_000004_create_mall_products_table.php' => 'CreateMallProductsTable',
            '2024_01_01_000008_create_mall_orders_table.php' => 'CreateMallOrdersTable',
            '2024_01_01_000009_create_mall_order_items_table.php' => 'CreateMallOrderItemsTable',
            '2024_01_01_000010_create_mall_order_addresses_table.php' => 'CreateMallOrderAddressesTable',
            '2024_01_01_000011_create_mall_order_packages_table.php' => 'CreateMallOrderPackagesTable',
            '2024_01_01_000013_create_mall_wallets_table.php' => 'CreateMallWalletsTable',
            '2024_01_01_000014_create_mall_wallet_transactions_table.php' => 'CreateMallWalletTransactionsTable',
            '2024_01_01_000026_create_mall_order_logs_table.php' => 'CreateMallOrderLogsTable',
            '2024_01_01_000028_create_mall_payments_table.php' => 'CreateMallPaymentsTable',
            '2026_10_15_000001_add_manual_order_payment_method.php' => 'AddManualOrderPaymentMethod',
        ];
        foreach ($migrations as $file => $class) {
            require_once BASE_PATH . '/migrations/' . $file;
            (new $class())->up();
        }
        $this->migrate('migrations/2026_10_16_000003_add_external_commerce_contracts.php');

        $this->orders = new DomainOrderService(
            new OrderRepository(new Order()),
            $this->createMock(DomainMallSettingService::class),
        );
        $repository = new OrderPaymentRepository(new OrderPayment());
        $this->payments = new DomainPayService(
            $this->orders,
            new DomainOrderPaymentService($repository),
            $repository,
            new DomainMemberWalletService(new MemberWalletRepository(new MemberWallet())),
            new MemberWalletTransactionRepository(new MemberWalletTransaction()),
            new PaymentPolicy(),
            new OrderPaymentRefundRepository(),
            new \App\Infrastructure\Plugin\NullExternalFulfillment(),
        );

        $this->connection->table('members')->insert(['id' => 7, 'nickname' => '测试买家']);
        $this->connection->table('wallets')->insert([
            'member_id' => 7, 'type' => 'balance', 'balance' => 10000,
            'total_consume' => 2000, 'pay_password' => 'existing-hash',
        ]);
        $this->connection->table('product_skus')->insert([
            'id' => 11, 'product_id' => 3, 'sku_code' => 'MANUAL-SKU',
            'sku_name' => '测试规格', 'sale_price' => 600, 'stock' => 8,
        ]);
    }

    public function testManualPaymentPersistsMatchingOrderPaymentLogAndEventWithoutWalletOrStockChanges(): void
    {
        $id = $this->seedOrder();
        $unrelated = $this->unrelatedState();

        $detail = $this->app()->pay($this->input($id));

        self::assertSame($id, (int) $detail['id']);
        self::assertSame('paid', $detail['status']);
        self::assertSame('paid', $detail['pay_status']);
        self::assertSame('manual', $detail['pay_method']);
        $order = $this->connection->table('orders')->where('id', $id)->first();
        self::assertSame('paid', $order->status);
        self::assertSame('paid', $order->pay_status);
        self::assertSame('manual', $order->pay_method);
        self::assertSame(600, (int) $order->pay_amount);
        self::assertNotEmpty($order->pay_no);
        self::assertNotNull($order->pay_time);

        self::assertSame(1, $this->connection->table('order_payments')->count());
        $payment = $this->connection->table('order_payments')->first();
        self::assertSame($id, (int) $payment->order_id);
        self::assertSame('MANUAL-ORDER', $payment->order_no);
        self::assertSame(7, (int) $payment->member_id);
        self::assertSame('manual', $payment->payment_method);
        self::assertSame('paid', $payment->status);
        self::assertSame(600, (int) $payment->payment_amount);
        self::assertSame(600, (int) $payment->paid_amount);
        self::assertSame(0, (int) $payment->refund_amount);
        self::assertSame($payment->payment_no, $order->pay_no);
        self::assertNotNull($payment->paid_at);

        self::assertSame(1, $this->connection->table('order_logs')->count());
        $log = $this->connection->table('order_logs')->first();
        self::assertSame($id, (int) $log->order_id);
        self::assertSame('manual_pay', $log->action);
        self::assertSame('admin', $log->operator_type);
        self::assertSame(42, (int) $log->operator_id);
        self::assertSame('认款管理员', $log->operator_name);
        self::assertSame('pending', $log->old_status);
        self::assertSame('paid', $log->new_status);
        self::assertStringContainsString('线下转账已核实', (string) $log->description . (string) $log->extra_data);

        self::assertSame(1, $this->connection->table('trade_outbox')->count());
        $event = $this->connection->table('trade_outbox')->first();
        self::assertSame('paid:MANUAL-ORDER', $event->business_key);
        self::assertSame('order.paid', $event->event_type);
        $payload = json_decode($event->payload, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($id, $payload['order_id']);
        self::assertSame('MANUAL-ORDER', $payload['order_no']);
        self::assertSame(7, $payload['member_id']);
        self::assertSame(600, $payload['amount']);
        self::assertNull($event->processed_at);
        self::assertSame($unrelated, $this->unrelatedState());
    }

    public function testReplayDoesNotDuplicatePaymentAuditOrEvent(): void
    {
        $id = $this->seedOrder();
        $app = $this->app();
        $app->pay($this->input($id));
        $before = $this->state();

        $replay = $this->input($id);
        $replay->remark = '重复请求不应生成新日志';
        $replay->operator_id = 43;
        $app->pay($replay);

        self::assertSame($before, $this->state());
    }

    public function testManualReplayAfterShippingStillDoesNotMutateAnything(): void
    {
        $id = $this->seedOrder();
        $app = $this->app();
        $app->pay($this->input($id));
        $this->connection->table('orders')->where('id', $id)->update(['status' => 'shipped', 'shipping_status' => 'shipped']);
        $before = $this->state();

        $app->pay($this->input($id));

        self::assertSame($before, $this->state());
    }

    public function testOutboxFailureRollsBackOrderPaymentAuditAndOutboxThenRetrySucceedsOnce(): void
    {
        $id = $this->seedOrder();
        $before = $this->state();
        $outbox = $this->createMock(TradeOutboxInterface::class);
        $outbox->expects(self::once())->method('append')->willReturnCallback(
            static function (string $key, string $type, array $payload): void {
                (new TradeOutboxRepository())->append($key, $type, $payload);
                throw new \RuntimeException('outbox write failed');
            },
        );

        try {
            $this->app($outbox)->pay($this->input($id));
            self::fail('An outbox failure must abort the whole payment transaction.');
        } catch (\RuntimeException $exception) {
            self::assertSame('outbox write failed', $exception->getMessage());
        }
        self::assertSame($before, $this->state());

        $app = $this->app();
        $app->pay($this->input($id));
        $app->pay($this->input($id));
        self::assertSame('paid', $this->connection->table('orders')->where('id', $id)->value('status'));
        self::assertSame(1, $this->connection->table('order_payments')->count());
        self::assertSame(1, $this->connection->table('order_logs')->count());
        self::assertSame(1, $this->connection->table('trade_outbox')->count());
        self::assertSame($before['wallets'], $this->rows('wallets'));
        self::assertSame($before['wallet_transactions'], $this->rows('wallet_transactions'));
        self::assertSame($before['product_skus'], $this->rows('product_skus'));
    }

    #[DataProvider('unpayableOrders')]
    public function testUnpayableOrderLeavesAllRecordsUntouched(array $attributes): void
    {
        $id = $this->seedOrder($attributes);

        $this->assertRejectedWithoutWrites($id);
    }

    public static function unpayableOrders(): array
    {
        return [
            'cancelled' => [['status' => 'cancelled', 'pay_status' => 'cancelled']],
            'cancelled with stale pending payment state' => [['status' => 'cancelled']],
            'expired' => [['expire_time' => '2000-01-01 00:00:00']],
            'zero amount' => [['pay_amount' => 0]],
            'failed payment state' => [['pay_status' => 'failed']],
            'paid by balance' => [['status' => 'paid', 'pay_status' => 'paid', 'pay_method' => 'balance']],
            'paid by wechat' => [['status' => 'paid', 'pay_status' => 'paid', 'pay_method' => 'wechat']],
        ];
    }

    #[DataProvider('pendingWechatPayments')]
    public function testPendingWechatPaymentMustBeReconciledBeforeManualPayment(?string $expiredAt): void
    {
        $id = $this->seedOrder();
        $this->seedPayment($id, 'wechat', 'pending', ['expired_at' => $expiredAt]);

        $this->assertRejectedWithoutWrites($id);
    }

    public static function pendingWechatPayments(): array
    {
        return [
            'open channel payment' => [null],
            'local expiry does not confirm channel closure' => ['2000-01-01 00:00:00'],
        ];
    }

    public function testPaidPaymentRecordRejectsManualPaymentEvenIfOrderStateIsStale(): void
    {
        $id = $this->seedOrder();
        $this->seedPayment($id, 'wechat', 'paid', ['paid_amount' => 600]);

        $this->assertRejectedWithoutWrites($id);
    }

    #[DataProvider('closedWechatPayments')]
    public function testClosedWechatRecordIsPreservedWhenManualPaymentSucceeds(string $status): void
    {
        $id = $this->seedOrder();
        $paymentId = $this->seedPayment($id, 'wechat', $status);
        $original = $this->connection->table('order_payments')->where('id', $paymentId)->first();

        $this->app()->pay($this->input($id));

        self::assertEquals($original, $this->connection->table('order_payments')->where('id', $paymentId)->first());
        self::assertSame(2, $this->connection->table('order_payments')->count());
        self::assertSame(1, $this->connection->table('order_payments')->where('payment_method', 'manual')->where('status', 'paid')->count());
        self::assertSame('manual', $this->connection->table('orders')->where('id', $id)->value('pay_method'));
    }

    public static function closedWechatPayments(): array
    {
        return ['cancelled' => ['cancelled'], 'failed' => ['failed']];
    }

    public function testLatestClosedPaymentDoesNotHideOlderPendingPayment(): void
    {
        $id = $this->seedOrder();
        $this->seedPayment($id, 'wechat', 'pending');
        $this->seedPayment($id, 'wechat', 'cancelled', ['payment_no' => 'NEWER-CLOSED-PAYMENT']);

        $this->assertRejectedWithoutWrites($id);
    }

    public function testConcurrentManualPaymentsProduceOnePaymentAuditAndEvent(): void
    {
        $this->seedOrder(['pay_method' => null]);
        $unrelated = $this->unrelatedState();

        $results = $this->race(['manual', 'manual']);

        self::assertSame([true, true], array_column($results, 'ok'));
        self::assertSame('paid', $this->connection->table('orders')->value('status'));
        self::assertSame('manual', $this->connection->table('orders')->value('pay_method'));
        self::assertSame(1, $this->connection->table('order_payments')->count());
        self::assertSame(1, $this->connection->table('order_logs')->count());
        self::assertSame(1, $this->connection->table('trade_outbox')->count());
        self::assertSame($unrelated, $this->unrelatedState());
    }

    public function testManualPaymentAndCancellationRaceHaveOneConsistentWinner(): void
    {
        $this->seedOrder(['pay_method' => null]);
        $unrelated = $this->unrelatedState();

        $results = $this->race(['manual', 'cancel']);

        self::assertSame(1, count(array_filter($results, static fn (array $result): bool => $result['ok'])));
        $order = $this->connection->table('orders')->first();
        self::assertContains($order->status, ['paid', 'cancelled']);
        self::assertSame($order->status, $order->pay_status);
        $paid = $order->status === 'paid';
        self::assertSame($paid ? 'manual' : null, $order->pay_method);
        self::assertSame($paid ? 1 : 0, $this->connection->table('order_payments')->count());
        self::assertSame($paid ? 1 : 0, $this->connection->table('order_logs')->count());
        self::assertSame($paid ? 1 : 0, $this->connection->table('trade_outbox')->count());
        self::assertSame($unrelated, $this->unrelatedState());
    }

    public function testStaleMappedOrderCannotOverwritePaymentWhenOnlySellerRemarkChanges(): void
    {
        $id = $this->seedOrder(['pay_method' => null]);
        $snapshot = $this->orders->getEntity($id);
        self::assertSame([], $snapshot->toArray());

        $this->app()->pay($this->input($id));
        $paid = (array) $this->connection->table('orders')->where('id', $id)->first();
        $snapshot->setSellerRemark('客服补充备注');
        self::assertSame(['seller_remark' => '客服补充备注'], $snapshot->toArray());
        $this->orders->update($snapshot);

        $updated = (array) $this->connection->table('orders')->where('id', $id)->first();
        self::assertSame('客服补充备注', $updated['seller_remark']);
        unset($paid['seller_remark'], $paid['updated_at'], $updated['seller_remark'], $updated['updated_at']);
        self::assertSame($paid, $updated);
    }

    public function testMappedPaymentStartsCleanAndCanExplicitlyClearNullableRemark(): void
    {
        $id = $this->seedOrder();
        $paymentId = $this->seedPayment($id, 'wechat', 'cancelled', ['remark' => '原支付已关闭']);
        $repository = new OrderPaymentRepository(new OrderPayment());
        $entity = (new DomainOrderPaymentService($repository))->getEntityByPaymentNo('ORIGINAL-PAYMENT');
        self::assertSame([], $entity->toArray());

        $entity->setRemark(null);
        self::assertSame(['remark' => null], $entity->toArray());
        $repository->updateById($paymentId, $entity->toArray());

        $payment = $this->connection->table('order_payments')->where('id', $paymentId)->first();
        self::assertNull($payment->remark);
        self::assertSame('cancelled', $payment->status);
        self::assertSame('wechat', $payment->payment_method);
    }

    private function race(array $modes): array
    {
        $barrier = tempnam(sys_get_temp_dir(), 'trade-race-');
        unlink($barrier);
        $workers = [];
        try {
            foreach ($modes as $mode) {
                $process = proc_open([PHP_BINARY, BASE_PATH . '/tests/Support/trade-concurrency-worker.php',
                    $this->connection->getDatabaseName(), $mode, 'MANUAL-ORDER', $barrier],
                    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
                self::assertIsResource($process);
                $workers[] = [$process, $pipes];
                self::assertSame("READY\n", fgets($pipes[1]));
            }
            touch($barrier);
            $results = [];
            foreach ($workers as [$process, $pipes]) {
                $result = stream_get_contents($pipes[1]);
                $error = stream_get_contents($pipes[2]);
                foreach ($pipes as $pipe) { fclose($pipe); }
                self::assertSame(0, proc_close($process), $error . $result);
                $results[] = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
            }
            $workers = [];
            return $results;
        } finally {
            foreach ($workers as [$process, $pipes]) {
                if (! is_resource($process)) { continue; }
                proc_terminate($process);
                foreach ($pipes as $pipe) { if (is_resource($pipe)) { fclose($pipe); } }
                proc_close($process);
            }
            if (is_file($barrier)) { unlink($barrier); }
        }
    }

    private function app(?TradeOutboxInterface $outbox = null): AppOrderManualPaymentService
    {
        return new AppOrderManualPaymentService($this->payments, $this->orders, new HyperfTransactionManager(), $outbox ?? new TradeOutboxRepository());
    }

    private function input(int $orderId): OrderManualPayDto
    {
        $input = new OrderManualPayDto();
        $input->order_id = $orderId;
        $input->remark = '线下转账已核实';
        $input->operator_id = 42;
        $input->operator_name = '认款管理员';
        return $input;
    }

    private function seedOrder(array $attributes = []): int
    {
        $id = (int) $this->connection->table('orders')->insertGetId(array_replace([
            'order_no' => 'MANUAL-ORDER', 'member_id' => 7,
            'goods_amount' => 600, 'total_amount' => 600, 'pay_amount' => 600,
            'status' => 'pending', 'pay_status' => 'pending', 'pay_method' => 'wechat',
            'expire_time' => date('Y-m-d H:i:s', time() + 3600),
        ], $attributes));
        $this->connection->table('order_items')->insert([
            'order_id' => $id, 'product_id' => 3, 'sku_id' => 11,
            'product_name' => '测试商品', 'sku_name' => '测试规格',
            'unit_price' => 600, 'quantity' => 1, 'total_price' => 600,
        ]);
        return $id;
    }

    private function seedPayment(int $orderId, string $method, string $status, array $attributes = []): int
    {
        return (int) $this->connection->table('order_payments')->insertGetId(array_replace([
            'payment_no' => 'ORIGINAL-PAYMENT', 'order_id' => $orderId, 'order_no' => 'MANUAL-ORDER',
            'member_id' => 7, 'payment_method' => $method, 'payment_amount' => 600, 'status' => $status,
        ], $attributes));
    }

    private function assertRejectedWithoutWrites(int $orderId): void
    {
        $before = $this->state();
        try {
            $this->app()->pay($this->input($orderId));
            self::fail('An unpayable order must reject manual payment.');
        } catch (\DomainException) {
            self::assertSame($before, $this->state());
        }
    }

    private function unrelatedState(): array
    {
        return array_intersect_key($this->state(), array_flip(['wallets', 'wallet_transactions', 'product_skus', 'order_items']));
    }

    private function state(): array
    {
        $state = [];
        foreach (['orders', 'order_payments', 'order_logs', 'trade_outbox', 'wallets', 'wallet_transactions', 'product_skus', 'order_items'] as $table) {
            $state[$table] = $this->rows($table);
        }
        return $state;
    }

    private function rows(string $table): array
    {
        return array_map(static fn (object $row): array => (array) $row, $this->connection->table($table)->orderBy('id')->get()->all());
    }
}
