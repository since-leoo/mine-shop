<?php

declare(strict_types=1);

namespace HyperfTests\Integration\Trade;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\PaymentSetting;
use App\Domain\Member\Repository\MemberWalletRepository;
use App\Domain\Member\Repository\MemberWalletTransactionRepository;
use App\Domain\Member\Service\DomainMemberWalletService;
use App\Domain\Trade\AfterSale\Repository\AfterSaleRepository;
use App\Domain\Trade\AfterSale\Service\DomainAfterSaleRefundCallbackService;
use App\Domain\Trade\AfterSale\Service\DomainAfterSaleRefundService;
use App\Domain\Trade\AfterSale\Service\DomainAfterSaleService;
use App\Domain\Trade\AfterSale\Service\DomainRefundSettlementService;
use App\Domain\Trade\Order\Repository\OrderPaymentRefundRepository;
use App\Domain\Trade\Order\Repository\OrderPaymentRepository;
use App\Domain\Trade\Order\Repository\OrderRepository;
use App\Domain\Trade\Order\Service\DomainOrderPaymentService;
use App\Domain\Trade\Order\Service\DomainOrderService;
use App\Domain\Trade\Payment\DomainPayService;
use App\Domain\Trade\Payment\ValueObject\PaymentPolicy;
use App\Domain\Trade\Shared\Contract\TradeOutboxInterface;
use App\Domain\Trade\Shared\Repository\TradeOutboxRepository;
use App\Infrastructure\Model\AfterSale\AfterSale;
use App\Infrastructure\Model\Member\MemberWallet;
use App\Infrastructure\Model\Member\MemberWalletTransaction;
use App\Infrastructure\Model\Order\Order;
use App\Infrastructure\Model\Order\OrderPayment;
use App\Infrastructure\Service\Trade\HyperfTransactionManager;
use HyperfTests\Support\DatabaseTestCase;

final class TradeMoneyTest extends DatabaseTestCase
{
    private DomainOrderService $orders;
    private DomainOrderPaymentService $payments;
    private OrderPaymentRepository $paymentRepo;
    private DomainMemberWalletService $wallets;
    private MemberWalletTransactionRepository $ledger;
    private DomainAfterSaleService $afterSales;
    private DomainPayService $pay;
    private HyperfTransactionManager $transactions;
    private OrderPaymentRefundRepository $refundRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $migrations = [
            '2024_01_01_000004_create_mall_products_table.php' => 'CreateMallProductsTable',
            '2024_01_01_000008_create_mall_orders_table.php' => 'CreateMallOrdersTable',
            '2024_01_01_000009_create_mall_order_items_table.php' => 'CreateMallOrderItemsTable',
            '2024_01_01_000010_create_mall_order_addresses_table.php' => 'CreateMallOrderAddressesTable',
            '2024_01_01_000011_create_mall_order_packages_table.php' => 'CreateMallOrderPackagesTable',
            '2024_01_01_000028_create_mall_payments_table.php' => 'CreateMallPaymentsTable',
            '2024_01_01_000029_create_mall_payment_refunds_table.php' => 'CreateMallPaymentRefundsTable',
            '2024_01_01_000013_create_mall_wallets_table.php' => 'CreateMallWalletsTable',
            '2024_01_01_000014_create_mall_wallet_transactions_table.php' => 'CreateMallWalletTransactionsTable',
            '2026_03_16_000001_create_after_sale_tables.php' => 'CreateAfterSaleTables',
        ];
        foreach ($migrations as $file => $class) {
            require_once BASE_PATH . '/migrations/' . $file;
            (new $class())->up();
        }
        $this->migrate('migrations/2026_10_16_000003_add_external_commerce_contracts.php');
        $settings = $this->createMock(DomainMallSettingService::class);
        $orderRepo = new OrderRepository(new Order());
        $this->orders = new DomainOrderService($orderRepo, $settings);
        $this->paymentRepo = new OrderPaymentRepository(new OrderPayment());
        $this->payments = new DomainOrderPaymentService($this->paymentRepo);
        $this->wallets = new DomainMemberWalletService(new MemberWalletRepository(new MemberWallet()));
        $this->ledger = new MemberWalletTransactionRepository(new MemberWalletTransaction());
        $this->pay = new DomainPayService($this->orders, $this->payments, $this->paymentRepo, $this->wallets, $this->ledger, new PaymentPolicy(), new OrderPaymentRefundRepository(), new \App\Infrastructure\Plugin\NullExternalFulfillment());
        $this->afterSales = new DomainAfterSaleService($orderRepo, new AfterSaleRepository(new AfterSale()), $settings);
        $this->transactions = new HyperfTransactionManager();
        $this->refundRepo = new OrderPaymentRefundRepository();
    }

    private function seedOrder(string $number, int $amount = 600, string $status = 'pending', string $method = 'balance'): int
    {
        return (int) $this->connection->table('orders')->insertGetId([
            'order_no' => $number, 'member_id' => 7, 'goods_amount' => $amount,
            'total_amount' => $amount, 'pay_amount' => $amount, 'status' => $status,
            'pay_status' => $status === 'paid' ? 'paid' : 'pending', 'pay_method' => $method,
        ]);
    }

    private function seedWallet(int $amount = 1000): void
    {
        $this->connection->table('wallets')->insert(['member_id' => 7, 'type' => 'balance', 'balance' => $amount, 'pay_password' => 'existing-hash']);
    }

    public function testBalancePaymentReplayIsIdempotentAndLedgerMatches(): void
    {
        $this->seedOrder('A');
        $this->seedWallet();
        $settings = new PaymentSetting(false, [], true, 0, true, []);
        $first = $this->transactions->run(fn () => $this->pay->payByBalance('A', 7, $settings));
        $second = $this->transactions->run(fn () => $this->pay->payByBalance('A', 7, $settings));
        self::assertNotNull($first);
        self::assertNull($second);
        self::assertSame(400, (int) $this->connection->table('wallets')->value('balance'));
        self::assertSame(600, (int) $this->connection->table('wallets')->value('total_consume'));
        self::assertSame('existing-hash', $this->connection->table('wallets')->value('pay_password'));
        self::assertSame(1, $this->connection->table('wallet_transactions')->count());
        self::assertSame(1, $this->connection->table('order_payments')->count());
    }

    public function testPaymentFailureRollsBackWalletOrderAndLedger(): void
    {
        $this->seedOrder('A');
        $this->seedWallet();
        try {
            $this->transactions->run(function (): void {
                $this->pay->payByBalance('A', 7, new PaymentSetting(false, [], true, 0, true, []));
                throw new \RuntimeException('commit failure');
            });
            self::fail('failure expected');
        } catch (\RuntimeException $e) {
            self::assertSame('commit failure', $e->getMessage());
        }
        self::assertSame(1000, (int) $this->connection->table('wallets')->value('balance'));
        self::assertSame('pending', $this->connection->table('orders')->value('status'));
        self::assertSame(0, $this->connection->table('order_payments')->count());
        self::assertSame(0, $this->connection->table('wallet_transactions')->count());
    }

    public function testDailyLimitUsesActualPaidRows(): void
    {
        $this->seedOrder('A', 400);
        $this->seedOrder('B', 300);
        $this->seedWallet();
        $setting = new PaymentSetting(false, [], true, 0, true, ['daily_limit' => 600]);
        $this->transactions->run(fn () => $this->pay->payByBalance('A', 7, $setting));
        try {
            $this->transactions->run(fn () => $this->pay->payByBalance('B', 7, $setting));
            self::fail('daily limit expected');
        } catch (\DomainException $e) {
            self::assertStringContainsString('daily_limit', $e->getMessage());
        }
        self::assertSame(600, (int) $this->connection->table('wallets')->value('balance'));
        self::assertSame('pending', $this->connection->table('orders')->where('order_no', 'B')->value('status'));
    }

    public function testWrongOwnerCannotMutatePayment(): void
    {
        $this->seedOrder('A');
        try {
            $this->transactions->run(fn () => $this->pay->payByBalance('A', 8, new PaymentSetting(false, [], true, 0, true, [])));
            self::fail('owner guard expected');
        } catch (\DomainException) {
            self::assertSame('pending', $this->connection->table('orders')->value('status'));
            self::assertSame(0, $this->connection->table('order_payments')->count());
        }
    }

    private function seedRefund(): string
    {
        $orderId = $this->seedOrder('REFUND-ORDER', 700, 'paid', 'wechat');
        $paymentId = $this->connection->table('order_payments')->insertGetId([
            'payment_no' => 'PAY1', 'order_id' => $orderId, 'order_no' => 'REFUND-ORDER', 'member_id' => 7,
            'payment_method' => 'wechat', 'payment_amount' => 700, 'paid_amount' => 700, 'status' => 'paid',
        ]);
        $afterSaleId = $this->connection->table('order_trade_after_sale')->insertGetId([
            'after_sale_no' => 'AS1', 'order_id' => $orderId, 'order_item_id' => 1, 'member_id' => 7,
            'type' => 'refund_only', 'status' => 'refunding', 'refund_status' => 'processing',
            'apply_amount' => 700, 'refund_amount' => 700, 'reason' => 'test',
        ]);
        $this->refundRepo->create([
            'refund_no' => 'REF1', 'payment_id' => $paymentId, 'payment_no' => 'PAY1',
            'order_id' => $orderId, 'order_no' => 'REFUND-ORDER', 'member_id' => 7,
            'refund_amount' => 700, 'status' => 'refunding',
            'extra_data' => ['after_sale_id' => $afterSaleId, 'payment_method' => 'wechat', 'paid_amount' => 700],
        ]);
        return 'REF1';
    }

    private function refundHandler(TradeOutboxInterface $outbox): DomainAfterSaleRefundCallbackService
    {
        return new DomainAfterSaleRefundCallbackService($this->refundRepo, $this->paymentRepo, $this->orders, $this->afterSales,
            new DomainRefundSettlementService($this->afterSales, $this->payments, $this->orders, $this->wallets, $this->ledger), $outbox);
    }

    public function testRefundMidwayFailureRollsBackAndRetryCompletesExactlyOnce(): void
    {
        $this->seedRefund();
        $payload = ['out_refund_no' => 'REF1', 'out_trade_no' => 'REFUND-ORDER', 'refund_status' => 'SUCCESS', 'amount' => ['refund' => 700, 'total' => 700]];
        $brokenOutbox = $this->createMock(TradeOutboxInterface::class);
        $brokenOutbox->method('append')->willThrowException(new \RuntimeException('outbox write failed'));
        try {
            $this->transactions->run(fn () => $this->refundHandler($brokenOutbox)->handleWechatRefundCallback($payload));
            self::fail('failure expected');
        } catch (\RuntimeException $e) {
            self::assertSame('outbox write failed', $e->getMessage());
        }
        self::assertSame('refunding', $this->refundRepo->findByRefundNo('REF1')->status);
        self::assertSame('refunding', $this->connection->table('order_trade_after_sale')->value('status'));
        self::assertSame(0, (int) $this->connection->table('order_payments')->value('refund_amount'));
        $handler = $this->refundHandler(new TradeOutboxRepository());
        $this->transactions->run(fn () => $handler->handleWechatRefundCallback($payload));
        $this->transactions->run(fn () => $handler->handleWechatRefundCallback($payload));
        self::assertSame('success', $this->refundRepo->findByRefundNo('REF1')->status);
        self::assertSame('completed', $this->connection->table('order_trade_after_sale')->value('status'));
        self::assertSame(700, (int) $this->connection->table('order_payments')->value('refund_amount'));
        self::assertSame(1, $this->connection->table('trade_outbox')->count());
    }

    public function testRefundAmountMismatchLeavesEveryRecordUntouched(): void
    {
        $this->seedRefund();
        try {
            $this->transactions->run(fn () => $this->refundHandler(new TradeOutboxRepository())->handleWechatRefundCallback([
                'out_refund_no' => 'REF1', 'out_trade_no' => 'REFUND-ORDER', 'refund_status' => 'SUCCESS', 'amount' => ['refund' => 1, 'total' => 700],
            ]));
            self::fail('amount mismatch expected');
        } catch (\DomainException) {
            self::assertSame('refunding', $this->refundRepo->findByRefundNo('REF1')->status);
            self::assertSame(0, (int) $this->connection->table('order_payments')->value('refund_amount'));
        }
    }
    public function testBalanceRefundRestoresWalletOnceWithoutInflatingRechargeTotal(): void
    {
        $this->seedRefund();
        $this->seedWallet(300);
        $this->connection->table('wallets')->update(['total_consume' => 700]);
        $this->connection->table('order_payments')->update(['payment_method' => 'balance']);
        $payload = ['out_refund_no' => 'REF1', 'out_trade_no' => 'REFUND-ORDER', 'status' => 'SUCCESS', 'amount' => ['refund' => 700, 'total' => 700]];
        $handler = $this->refundHandler(new TradeOutboxRepository());
        $this->transactions->run(fn () => $handler->complete($payload, 'balance'));
        $this->transactions->run(fn () => $handler->complete($payload, 'balance'));
        self::assertSame(1000, (int) $this->connection->table('wallets')->value('balance'));
        self::assertSame(0, (int) $this->connection->table('wallets')->value('total_recharge'));
        self::assertSame(0, (int) $this->connection->table('wallets')->value('total_consume'));
        self::assertSame(1, $this->connection->table('wallet_transactions')->count());
    }

    public function testOutboxListenerFailureRollsBackAndReplaysBeforeMarkingProcessed(): void
    {
        $this->seedWallet(100);
        $outbox = new TradeOutboxRepository();
        $outbox->append('paid:TEST', 'order.paid', ['member_id' => 7, 'order_no' => 'TEST', 'order_id' => 1, 'amount' => 600]);
        $attempts = 0;
        $events = $this->createMock(\Psr\EventDispatcher\EventDispatcherInterface::class);
        $events->method('dispatch')->willReturnCallback(function (object $event) use (&$attempts): object {
            if (! $event instanceof \App\Domain\Member\Event\OrderPaidForMember) { return $event; }
            $this->connection->table('wallets')->increment('balance', 10);
            if (++$attempts === 1) { throw new \RuntimeException('listener failure after write'); }
            return $event;
        });
        $recovery = new \App\Application\Api\Order\AppTradeRecoveryService(
            new \App\Domain\Trade\Order\Repository\OrderSubmissionRepository(),
            $this->createMock(\App\Application\Api\Order\AppOrderSubmissionService::class), $this->refundRepo,
            $this->createMock(\App\Application\Admin\Trade\AppAfterSaleRefundService::class), $outbox,
            $this->createMock(\App\Domain\Trade\Order\Contract\StockReservationInterface::class), $this->orders,
            $this->transactions, $events, new \Psr\Log\NullLogger(),
            $this->createMock(\App\Application\Api\Payment\AppPaymentReconciliationService::class));
        $recovery->publish();
        self::assertSame(100, (int) $this->connection->table('wallets')->value('balance'));
        self::assertNull($this->connection->table('trade_outbox')->value('processed_at'));
        self::assertSame(1, (int) $this->connection->table('trade_outbox')->value('attempts'));
        $recovery->publish();
        $recovery->publish();
        self::assertSame(110, (int) $this->connection->table('wallets')->value('balance'));
        self::assertSame(2, $attempts);
        self::assertNotNull($this->connection->table('trade_outbox')->value('processed_at'));
    }

    private function wechatSettings(): DomainMallSettingService
    {
        $settings = $this->createMock(DomainMallSettingService::class);
        $settings->method('payment')->willReturn(new PaymentSetting(true, [
            'mchid' => 'm1', 'app_id' => 'a1', 'private_key' => 'local-test-key', 'apiv3_key' => str_repeat('k', 32),
        ], true, 0, true, []));
        return $settings;
    }

    private function pendingWechat(string $number = 'WX'): array
    {
        $id = $this->seedOrder($number, 600, 'pending', 'wechat');
        $this->connection->table('order_payments')->insert([
            'payment_no' => 'PAY-' . $number, 'order_id' => $id, 'order_no' => $number, 'member_id' => 7,
            'payment_method' => 'wechat', 'payment_amount' => 600, 'status' => 'pending',
        ]);
        return ['out_trade_no' => $number, 'trade_state' => 'SUCCESS', 'transaction_id' => 'wx-' . $number,
            'mchid' => 'm1', 'appid' => 'a1', 'amount' => ['total' => 600, 'currency' => 'CNY']];
    }

    private function paymentCallbacks(): \App\Application\Api\Payment\AppPaymentCallbackService
    {
        return new \App\Application\Api\Payment\AppPaymentCallbackService($this->pay,
            $this->refundHandler(new TradeOutboxRepository()), $this->wechatSettings(),
            new \App\Infrastructure\Service\Pay\WechatPayConfigResolver(), $this->transactions, new TradeOutboxRepository());
    }

    public function testWechatDuplicateCallbackCommitsOnePaymentAndOneEvent(): void
    {
        $payload = $this->pendingWechat();
        $app = $this->paymentCallbacks();
        $app->paid($payload);
        $app->paid($payload);
        self::assertSame('paid', $this->connection->table('orders')->value('status'));
        self::assertSame(600, (int) $this->connection->table('order_payments')->value('paid_amount'));
        self::assertSame('wx-WX', $this->connection->table('order_payments')->value('third_party_no'));
        self::assertSame(1, $this->connection->table('trade_outbox')->count());
    }

    public function testLatePaymentOnCancelledOrderQueuesExactlyOneFullRefund(): void
    {
        $payload = $this->pendingWechat();
        $this->connection->table('orders')->update(['status' => 'cancelled', 'pay_status' => 'cancelled']);
        $app = $this->paymentCallbacks();
        $app->paid($payload);
        $app->paid($payload);
        self::assertSame('cancelled', $this->connection->table('orders')->value('status'));
        self::assertSame(0, $this->connection->table('trade_outbox')->count());
        self::assertSame(1, $this->connection->table('payment_refunds')->count());
        $refund = $this->connection->table('payment_refunds')->first();
        self::assertSame(600, (int) $refund->refund_amount);
        $this->transactions->run(fn () => $this->refundHandler(new TradeOutboxRepository())->complete([
            'out_refund_no' => $refund->refund_no, 'out_trade_no' => 'WX', 'status' => 'SUCCESS', 'amount' => ['refund' => 600, 'total' => 600],
        ], 'wechat'));
        self::assertSame('refunded', $this->connection->table('orders')->value('pay_status'));
        self::assertSame(0, $this->connection->table('trade_outbox')->count());
    }

    public function testFailedChannelCloseLeavesOrderAndCouponUntouched(): void
    {
        $this->pendingWechat();
        $gateway = $this->createMock(\App\Domain\Trade\Payment\Contract\PaymentGatewayInterface::class);
        $gateway->method('query')->willReturn(['trade_state' => 'NOTPAY']);
        $gateway->method('close')->willThrowException(new \RuntimeException('channel timeout'));
        $coupons = $this->createMock(\App\Domain\Trade\Order\Contract\CouponServiceInterface::class);
        $coupons->expects(self::never())->method('releaseOrderCoupons');
        $cancel = new \App\Domain\Trade\Order\Service\DomainOrderCancellationService($this->orders, $coupons, $gateway,
            $this->wechatSettings(), new \App\Infrastructure\Service\Pay\WechatPayConfigResolver());
        try {
            $this->transactions->run(fn () => $cancel->cancel('WX', 7, false));
            self::fail('close failure expected');
        } catch (\RuntimeException $e) {
            self::assertSame('channel timeout', $e->getMessage());
        }
        self::assertSame('pending', $this->connection->table('orders')->value('status'));
        self::assertSame('pending', $this->connection->table('orders')->value('pay_status'));
    }

    public function testLostPaymentCallbackRecoveredByChannelQuery(): void
    {
        $payload = $this->pendingWechat();
        $gateway = $this->createMock(\App\Domain\Trade\Payment\Contract\PaymentGatewayInterface::class);
        $gateway->expects(self::once())->method('query')->willReturn($payload);
        $recovery = new \App\Application\Api\Payment\AppPaymentReconciliationService($this->paymentRepo, $this->paymentCallbacks(),
            $gateway, $this->wechatSettings(), new \App\Infrastructure\Service\Pay\WechatPayConfigResolver(), new \Psr\Log\NullLogger());
        $recovery->recover();
        $recovery->recover();
        self::assertSame('paid', $this->connection->table('orders')->value('status'));
        self::assertSame(1, $this->connection->table('trade_outbox')->count());
    }

    public function testChannelTerminalStateStopsPollingButCannotOverwritePaymentSuccess(): void
    {
        $payload = $this->pendingWechat();
        $gateway = $this->createMock(\App\Domain\Trade\Payment\Contract\PaymentGatewayInterface::class);
        $gateway->expects(self::once())->method('query')->willReturn(['trade_state' => 'CLOSED']);
        $recovery = new \App\Application\Api\Payment\AppPaymentReconciliationService($this->paymentRepo, $this->paymentCallbacks(),
            $gateway, $this->wechatSettings(), new \App\Infrastructure\Service\Pay\WechatPayConfigResolver(), new \Psr\Log\NullLogger());
        $recovery->recover();
        $recovery->recover();
        self::assertSame('cancelled', $this->connection->table('order_payments')->value('status'));
        $this->paymentCallbacks()->paid($payload);
        $id = (int) $this->connection->table('order_payments')->value('id');
        $this->paymentRepo->closePendingFromChannel($id, 'CLOSED');
        self::assertSame('paid', $this->connection->table('order_payments')->value('status'));
    }

    public function testAutoRefundApprovalPersistsRequestBeforeAnyChannelCall(): void
    {
        $this->seedRefund();
        $this->connection->table('payment_refunds')->delete();
        $this->connection->table('order_trade_after_sale')->update(['status' => 'pending_review', 'refund_status' => 'pending']);
        $id = (int) $this->connection->table('order_trade_after_sale')->value('id');
        $input = $this->createMock(\App\Domain\Trade\AfterSale\Contract\AfterSaleReviewInput::class);
        $input->method('getId')->willReturn($id);
        $input->method('getApprovedRefundAmount')->willReturn(700);
        $input->method('getOperatorId')->willReturn(1);
        $input->method('getOperatorName')->willReturn('tester');
        $domain = new DomainAfterSaleRefundService($this->afterSales, $this->orders, $this->paymentRepo, $this->refundRepo);
        $refund = $this->transactions->run(fn () => $domain->approve($input, true));
        self::assertNotNull($refund);
        self::assertSame('requested', $this->refundRepo->findByRefundNo($refund->refund_no)->status);
        self::assertSame('refunding', $this->connection->table('order_trade_after_sale')->value('status'));
        $replayed = $this->transactions->run(fn () => $domain->prepare($id, 1, 'tester'));
        self::assertSame($refund->refund_no, $replayed->refund_no);
        self::assertSame(1, $this->connection->table('payment_refunds')->count());
    }

    public function testUnknownRefundQueriesThenResendsOriginalIdOnlyAfterNotFound(): void
    {
        $this->seedRefund();
        $this->refundRepo->updateByRefundNo('REF1', ['status' => 'requested']);
        $gateway = $this->createMock(\App\Domain\Trade\Payment\Contract\PaymentGatewayInterface::class);
        $requests = [];
        $gateway->expects(self::exactly(2))->method('refund')->willReturnCallback(function (array $payload) use (&$requests): array {
            $requests[] = $payload['out_refund_no'];
            if (count($requests) === 1) {
                throw new \RuntimeException('timeout after submission');
            }
            return ['out_refund_no' => 'REF1', 'out_trade_no' => 'REFUND-ORDER', 'status' => 'SUCCESS', 'amount' => ['refund' => 700, 'total' => 700]];
        });
        $queries = 0;
        $gateway->expects(self::exactly(2))->method('queryRefund')->willReturnCallback(function () use (&$queries): array {
            if (++$queries === 1) {
                throw new \RuntimeException('query timeout');
            }
            return ['status' => 'NOT_FOUND'];
        });
        $app = new \App\Application\Admin\Trade\AppAfterSaleRefundService(
            new DomainAfterSaleRefundService($this->afterSales, $this->orders, $this->paymentRepo, $this->refundRepo),
            $this->refundHandler(new TradeOutboxRepository()), $this->refundRepo, $gateway, $this->wechatSettings(),
            new \App\Infrastructure\Service\Pay\WechatPayConfigResolver(), $this->transactions, new \Psr\Log\NullLogger());
        $app->process('REF1');
        self::assertSame('unknown', $this->refundRepo->findByRefundNo('REF1')->status);
        $app->process('REF1');
        self::assertSame(['REF1'], $requests);
        $app->process('REF1');
        $app->process('REF1');
        self::assertSame(['REF1', 'REF1'], $requests);
        self::assertSame('success', $this->refundRepo->findByRefundNo('REF1')->status);
        self::assertSame(700, (int) $this->connection->table('order_payments')->value('refund_amount'));
        self::assertSame(1, $this->connection->table('payment_refunds')->count());
    }

    public function testParallelOrdersCannotOverspendTheSameWallet(): void
    {
        $this->seedOrder('A', 600);
        $this->seedOrder('B', 600);
        $this->seedWallet(1000);
        $results = $this->race([['pay', 'A'], ['pay', 'B']]);
        self::assertSame(1, count(array_filter($results, static fn ($r) => $r['ok'])));
        self::assertSame(400, (int) $this->connection->table('wallets')->value('balance'));
        self::assertSame(1, $this->connection->table('order_payments')->count());
        self::assertSame(1, $this->connection->table('wallet_transactions')->count());
    }

    public function testPaymentAndCancellationRaceHasOneConsistentWinner(): void
    {
        $this->seedOrder('A', 600);
        $this->seedWallet(1000);
        $results = $this->race([['pay', 'A'], ['cancel', 'A']]);
        self::assertSame(1, count(array_filter($results, static fn ($r) => $r['ok'])));
        $status = $this->connection->table('orders')->value('status');
        self::assertContains($status, ['paid', 'cancelled']);
        self::assertSame($status === 'paid' ? 400 : 1000, (int) $this->connection->table('wallets')->value('balance'));
        self::assertSame($status === 'paid' ? 1 : 0, $this->connection->table('order_payments')->count());
        self::assertSame($status === 'paid' ? 1 : 0, $this->connection->table('wallet_transactions')->count());
    }

    private function race(array $commands): array
    {
        $barrier = tempnam(sys_get_temp_dir(), 'trade-race-');
        unlink($barrier);
        $workers = [];
        try {
            foreach ($commands as [$mode, $orderNo]) {
                $process = proc_open([PHP_BINARY, BASE_PATH . '/tests/Support/trade-concurrency-worker.php',
                    $this->connection->getDatabaseName(), $mode, $orderNo, $barrier], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
                self::assertIsResource($process);
                self::assertSame("READY\n", fgets($pipes[1]));
                $workers[] = [$process, $pipes];
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

}
