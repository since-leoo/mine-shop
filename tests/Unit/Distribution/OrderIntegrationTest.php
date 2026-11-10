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

namespace HyperfTests\Unit\Distribution;

use App\Application\Admin\Trade\AppAfterSaleRefundService;
use App\Application\Api\Order\AppApiOrderCommandService;
use App\Application\Api\Order\AppOrderCancellationService;
use App\Application\Api\Order\AppOrderSubmissionService;
use App\Application\Api\Order\AppTradeRecoveryService;
use App\Application\Api\Payment\AppPaymentReconciliationService;
use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\OrderSetting;
use App\Domain\Infrastructure\SystemSetting\ValueObject\PaymentSetting;
use App\Domain\Trade\Order\Api\Command\DomainApiOrderCommandService;
use App\Domain\Trade\Order\Contract\OrderJobQueueInterface;
use App\Domain\Trade\Order\Contract\OrderSubmissionStoreInterface;
use App\Domain\Trade\Order\Contract\OrderSubmitInput;
use App\Domain\Trade\Order\Contract\StockReservationInterface;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Event\OrderPaidIntegrationEvent;
use App\Domain\Trade\Order\Repository\OrderPaymentRefundRepository;
use App\Domain\Trade\Order\Repository\OrderRepository;
use App\Domain\Trade\Order\Service\DomainOrderCreationService;
use App\Domain\Trade\Order\Service\DomainOrderService;
use App\Domain\Trade\Shared\Contract\TradeOutboxInterface;
use App\Domain\Trade\Shared\Contract\TransactionManagerInterface;
use App\Domain\Trade\Shared\Repository\TradeOutboxRepository;
use App\Infrastructure\Crontab\OrderAutoConfirmCrontab;
use Hyperf\Context\ApplicationContext;
use Hyperf\Database\ConnectionInterface;
use Hyperf\Database\ConnectionResolverInterface;
use Hyperf\Database\Model\Collection;
use Hyperf\DbConnection\Db;
use HyperfTests\Support\TestContainer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Plugin\Distribution\Application\AppDistributionTradeService;
use Plugin\Distribution\Infrastructure\Listener\DistributionTradeListener;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;

/**
 * @internal
 * @coversNothing
 */
final class OrderIntegrationTest extends TestCase
{
    private ?ContainerInterface $previousContainer;

    private bool $insideTransaction = false;

    private array $trace = [];

    protected function setUp(): void
    {
        $this->previousContainer = ApplicationContext::hasContainer() ? ApplicationContext::getContainer() : null;
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('transaction')->willReturnCallback(fn (\Closure $callback) => $this->transaction($callback));
        $resolver = $this->createMock(ConnectionResolverInterface::class);
        $resolver->method('connection')->willReturn($connection);
        $container = new TestContainer([ConnectionResolverInterface::class => $resolver]);
        $container->set(Db::class, new Db($container));
        ApplicationContext::setContainer($container);
    }

    protected function tearDown(): void
    {
        (new \ReflectionProperty(ApplicationContext::class, 'container'))->setValue(null, $this->previousContainer);
    }

    public function testApiSubmissionUsesTheSharedSubmissionExtensionPath(): void
    {
        $input = $this->createMock(OrderSubmitInput::class);
        $input->method('getMemberId')->willReturn(7);
        $payload = ['snapshot' => ['member_id' => 7], 'items' => [['sku_id' => 2]]];
        $domain = $this->createMock(DomainApiOrderCommandService::class);
        $domain->method('prepareSubmission')->with($input)->willReturn($payload);
        $submissions = $this->createMock(AppOrderSubmissionService::class);
        $submissions->expects(self::once())->method('submit')->with(self::matchesRegularExpression('/^ORD/'), 7, $payload);
        $settings = $this->createMock(DomainMallSettingService::class);
        $settings->method('payment')->willReturn(new PaymentSetting(false, [], true, 0, true, []));
        $app = new AppApiOrderCommandService($domain, $settings, $submissions, $this->createMock(AppOrderCancellationService::class), $this->createMock(OrderSubmissionStoreInterface::class), $this->createMock(TradeOutboxInterface::class));
        self::assertSame('processing', $app->submit($input)['status']);
    }

    public function testPluginEnrichesPreparingEventBeforeSubmissionBecomesDurable(): void
    {
        $payload = ['snapshot' => ['member_id' => 7], 'items' => [], 'stock_key' => 'stock'];
        $snapshot = ['buyer_id' => 7, 'member_id' => 3, 'rule_version' => 4];
        $distribution = $this->createMock(AppDistributionTradeService::class);
        $distribution->expects(self::once())->method('snapshot')->with(7, $payload)->willReturnCallback(function () use ($snapshot): array {
            self::assertFalse($this->insideTransaction);
            $this->trace[] = 'snapshot';
            return $snapshot;
        });
        $this->dispatcher($distribution);
        $store = $this->createMock(OrderSubmissionStoreInterface::class);
        $store->expects(self::once())->method('create')->with('ORDER-1', 7, $payload + ['distribution' => $snapshot])->willReturnCallback(function (): void {
            $this->trace[] = 'durable';
        });
        $store->method('lock')->willReturn(['status' => 'pending']);
        $store->expects(self::once())->method('updateStatus')->with('ORDER-1', 'reserved');
        $stock = $this->createMock(StockReservationInterface::class);
        $stock->expects(self::once())->method('reserve')->with('ORDER-1', [], 'stock');
        $queue = $this->createMock(OrderJobQueueInterface::class);
        $queue->expects(self::once())->method('push')->with('ORDER-1')->willReturnCallback(function (): bool {
            $this->trace[] = 'queue';
            return true;
        });
        (new AppOrderSubmissionService($store, $stock, $queue, $this->createMock(OrderRepository::class), $this->createMock(DomainOrderCreationService::class), $this->transactions(), $this->createMock(TradeOutboxInterface::class), new NullLogger()))->submit('ORDER-1', 7, $payload);
        self::assertSame(['snapshot', 'durable', 'begin', 'commit', 'queue'], $this->trace);
    }

    public function testPreparingEventFailureCannotPersistAnOrderWithoutItsFinancialAttribution(): void
    {
        $distribution = $this->createMock(AppDistributionTradeService::class);
        $distribution->method('snapshot')->willThrowException(new \RuntimeException('rule snapshot unavailable'));
        $this->dispatcher($distribution);
        $store = $this->createMock(OrderSubmissionStoreInterface::class);
        $store->expects(self::never())->method('create');
        $queue = $this->createMock(OrderJobQueueInterface::class);
        $queue->expects(self::never())->method('push');
        $this->expectExceptionMessage('rule snapshot unavailable');
        (new AppOrderSubmissionService($store, $this->createMock(StockReservationInterface::class), $queue, $this->createMock(OrderRepository::class), $this->createMock(DomainOrderCreationService::class), $this->transactions(), $this->createMock(TradeOutboxInterface::class), new NullLogger()))->submit('ORDER-1', 7, ['items' => []]);
    }

    public function testOrderCreationPersistsDistributionSnapshotBeforeTheSameTransactionCommits(): void
    {
        $snapshot = ['buyer_id' => 7, 'member_id' => 3, 'rule_version' => 4];
        $payload = ['items' => [], 'stock_key' => 'stock', 'snapshot' => ['member_id' => 7], 'distribution' => $snapshot];
        $store = $this->createMock(OrderSubmissionStoreInterface::class);
        $store->method('lock')->willReturn(['status' => 'reserved', 'payload' => $payload]);
        $store->expects(self::once())->method('updateStatus')->with('ORDER-1', 'created');
        $orders = $this->createMock(OrderRepository::class);
        $orders->method('findExistingByOrderNo')->willReturn(null);
        $creation = $this->createMock(DomainOrderCreationService::class);
        $creation->expects(self::once())->method('create')->with('ORDER-1', $payload)->willReturnCallback(function (): OrderEntity {
            self::assertTrue($this->insideTransaction);
            $this->trace[] = 'order';
            return new OrderEntity();
        });
        $distribution = $this->createMock(AppDistributionTradeService::class);
        $distribution->expects(self::once())->method('created')->with('ORDER-1', $snapshot)->willReturnCallback(function (): void {
            self::assertTrue($this->insideTransaction);
            $this->trace[] = 'snapshot';
        });
        $this->dispatcher($distribution);
        $outbox = $this->createMock(TradeOutboxInterface::class);
        $outbox->expects(self::once())->method('append')->with('created:ORDER-1', 'order.created', ['order_no' => 'ORDER-1', 'from_cart' => false])->willReturnCallback(function (): void {
            self::assertTrue($this->insideTransaction);
            $this->trace[] = 'outbox';
        });
        (new AppOrderSubmissionService($store, $this->createMock(StockReservationInterface::class), $this->createMock(OrderJobQueueInterface::class), $orders, $creation, $this->transactions(), $outbox, new NullLogger()))->process('ORDER-1');
        self::assertSame(['begin', 'order', 'snapshot', 'outbox', 'commit'], $this->trace);
    }

    public function testSnapshotFailurePreventsCreatedStatusAndOutboxAcknowledgement(): void
    {
        $store = $this->createMock(OrderSubmissionStoreInterface::class);
        $store->method('lock')->willReturn(['status' => 'reserved', 'payload' => ['items' => [], 'stock_key' => 'stock', 'distribution' => ['buyer_id' => 7]]]);
        $store->expects(self::never())->method('updateStatus');
        $orders = $this->createMock(OrderRepository::class);
        $orders->method('findExistingByOrderNo')->willReturn(null);
        $creation = $this->createMock(DomainOrderCreationService::class);
        $creation->method('create')->willReturn(new OrderEntity());
        $distribution = $this->createMock(AppDistributionTradeService::class);
        $distribution->method('created')->willThrowException(new \RuntimeException('snapshot persistence failed'));
        $this->dispatcher($distribution);
        $outbox = $this->createMock(TradeOutboxInterface::class);
        $outbox->expects(self::never())->method('append');
        try {
            (new AppOrderSubmissionService($store, $this->createMock(StockReservationInterface::class), $this->createMock(OrderJobQueueInterface::class), $orders, $creation, $this->transactions(), $outbox, new NullLogger()))->process('ORDER-1');
            self::fail('Snapshot persistence failure must abort the order transaction.');
        } catch (\RuntimeException $error) {
            self::assertSame('snapshot persistence failed', $error->getMessage());
        }
        self::assertSame(['begin', 'rollback'], $this->trace);
    }

    public function testReceiptAndCompletionOutboxAreWrittenUnderTheSameOrderLock(): void
    {
        $entity = new OrderEntity();
        $entity->setId(11);
        $entity->setMemberId(7);
        $entity->setOrderNo('ORDER-1');
        $domain = $this->createMock(DomainApiOrderCommandService::class);
        $domain->expects(self::once())->method('getLockedEntity')->with('ORDER-1')->willReturnCallback(function () use ($entity): OrderEntity {
            self::assertTrue($this->insideTransaction);
            $this->trace[] = 'lock';
            return $entity;
        });
        $domain->expects(self::once())->method('confirmReceipt')->with($entity)->willReturnCallback(function () use ($entity): OrderEntity {
            self::assertTrue($this->insideTransaction);
            $this->trace[] = 'complete';
            return $entity;
        });
        $outbox = $this->createMock(TradeOutboxInterface::class);
        $outbox->expects(self::once())->method('append')->with('completed:ORDER-1', 'order.completed', self::callback(static function (array $payload): bool {
            return $payload['order_id'] === 11 && $payload['order_no'] === 'ORDER-1' && $payload['member_id'] === 7
                && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $payload['completed_at'] ?? '') === 1;
        }))->willReturnCallback(function (): void {
            self::assertTrue($this->insideTransaction);
            $this->trace[] = 'outbox';
        });
        (new AppApiOrderCommandService($domain, $this->createMock(DomainMallSettingService::class), $this->createMock(AppOrderSubmissionService::class), $this->createMock(AppOrderCancellationService::class), $this->createMock(OrderSubmissionStoreInterface::class), $outbox))->confirmReceipt(7, 'ORDER-1');
        self::assertSame(['begin', 'lock', 'complete', 'outbox', 'commit'], $this->trace);
    }

    #[DataProvider('provideDurableTradeEventsReachDistributionBeforeBeingAcknowledgedCases')]
    public function testDurableTradeEventsReachDistributionBeforeBeingAcknowledged(string $type, string $method): void
    {
        $row = (object) ['id' => 12, 'event_type' => $type, 'processed_at' => null, 'payload' => json_encode($this->eventPayload())];
        $outbox = $this->createMock(TradeOutboxRepository::class);
        $outbox->method('pending')->willReturn([$row]);
        $outbox->method('lock')->with(12)->willReturn($row);
        $distribution = $this->createMock(AppDistributionTradeService::class);
        $arguments = $method === 'recordCompletion' ? [11, '2026-10-16 12:00:00'] : [11];
        $distribution->expects(self::once())->method($method)->with(...$arguments)->willReturnCallback(function (): void {
            self::assertTrue($this->insideTransaction);
            $this->trace[] = 'distribution';
        });
        $events = $this->dispatcher($distribution, true, $type === 'order.paid' ? 2 : 1);
        $outbox->expects(self::once())->method('complete')->with(12)->willReturnCallback(function () use ($row): void {
            self::assertTrue($this->insideTransaction);
            $row->processed_at = '2026-10-16 10:00:00';
            $this->trace[] = 'ack';
        });
        $outbox->expects(self::never())->method('recordFailure');
        $service = $this->recovery($outbox, $events);
        $service->publish();
        $service->publish();
        self::assertSame($type === 'order.paid'
            ? ['begin', 'integration_event', 'distribution', 'event', 'ack', 'commit', 'begin', 'commit']
            : ['begin', 'distribution', 'event', 'ack', 'commit', 'begin', 'commit'], $this->trace);
    }

    public static function provideDurableTradeEventsReachDistributionBeforeBeingAcknowledgedCases(): iterable
    {
        yield ['order.paid', 'paid'];
        yield ['refund.succeeded', 'refunded'];
        yield ['order.completed', 'recordCompletion'];
    }

    public function testDistributionFailureLeavesTradeEventPendingForRetry(): void
    {
        $row = (object) ['id' => 12, 'event_type' => 'order.paid', 'processed_at' => null, 'payload' => json_encode($this->eventPayload())];
        $outbox = $this->createMock(TradeOutboxRepository::class);
        $outbox->method('pending')->willReturn([$row]);
        $outbox->method('lock')->willReturn($row);
        $outbox->expects(self::never())->method('complete');
        $outbox->expects(self::once())->method('recordFailure')->with(12, 'commission persistence failed');
        $distribution = $this->createMock(AppDistributionTradeService::class);
        $distribution->method('paid')->willThrowException(new \RuntimeException('commission persistence failed'));
        $events = $this->dispatcher($distribution, true, 2);
        $this->recovery($outbox, $events)->publish();
        self::assertSame(['begin', 'integration_event', 'rollback'], $this->trace);
        self::assertNull($row->processed_at);
    }

    public function testAutoConfirmationUsesTheSameApplicationPathAndContinuesAfterOneFailure(): void
    {
        $orders = $this->createMock(OrderRepository::class);
        $orders->method('findAutoConfirmableOrders')->willReturn(new Collection([(object) ['id' => 1, 'member_id' => 7, 'order_no' => 'FIRST'], (object) ['id' => 2, 'member_id' => 8, 'order_no' => 'SECOND']]));
        $orders->expects(self::never())->method('complete');
        $settings = $this->createMock(DomainMallSettingService::class);
        $settings->method('order')->willReturn(new OrderSetting(30, 7, 7, false, '', ''));
        $app = $this->createMock(AppApiOrderCommandService::class);
        $called = [];
        $app->expects(self::exactly(2))->method('confirmReceipt')->willReturnCallback(static function (int $member, string $order) use (&$called): void {
            $called[] = [$member, $order];
            if ($order === 'FIRST') {
                throw new \RuntimeException('Order state changed since batch selection.');
            }
        });
        (new OrderAutoConfirmCrontab($orders, $settings, new NullLogger(), $app))->execute();
        self::assertSame([[7, 'FIRST'], [8, 'SECOND']], $called);
    }

    private function eventPayload(): array
    {
        return ['order_id' => 11, 'order_no' => 'ORDER-1', 'member_id' => 7, 'amount' => 1000, 'after_sale_id' => 1, 'refund_amount' => 100, 'payment_id' => 2, 'payment_no' => 'PAY-1', 'payment_method' => 'balance', 'refund_no' => 'REF-1', 'operator_id' => 0, 'operator_name' => 'system', 'completed_at' => '2026-10-16 12:00:00'];
    }

    private function transaction(callable $callback): mixed
    {
        $this->insideTransaction = true;
        $this->trace[] = 'begin';
        try {
            $result = $callback();
            $this->trace[] = 'commit';
            return $result;
        } catch (\Throwable $error) {
            $this->trace[] = 'rollback';
            throw $error;
        } finally {
            $this->insideTransaction = false;
        }
    }

    private function transactions(): TransactionManagerInterface
    {
        $transactions = $this->createMock(TransactionManagerInterface::class);
        $transactions->method('run')->willReturnCallback(fn (callable $callback) => $this->transaction($callback));
        return $transactions;
    }

    private function dispatcher(AppDistributionTradeService $distribution, bool $trace = false, ?int $count = null): EventDispatcherInterface
    {
        $listener = new DistributionTradeListener($distribution);
        $events = $this->createMock(EventDispatcherInterface::class);
        $events->expects($count === null ? self::any() : self::exactly($count))->method('dispatch')->willReturnCallback(function (object $event) use ($listener, $trace): object {
            if ($trace) {
                self::assertTrue($this->insideTransaction);
            }
            if (\in_array($event::class, $listener->listen(), true)) {
                $listener->process($event);
            }
            if ($trace) {
                $this->trace[] = $event instanceof OrderPaidIntegrationEvent ? 'integration_event' : 'event';
            }
            return $event;
        });
        ApplicationContext::getContainer()->set(EventDispatcherInterface::class, $events);
        return $events;
    }

    private function recovery(TradeOutboxRepository $outbox, EventDispatcherInterface $events): AppTradeRecoveryService
    {
        return new AppTradeRecoveryService($this->createMock(OrderSubmissionStoreInterface::class), $this->createMock(AppOrderSubmissionService::class), $this->createMock(OrderPaymentRefundRepository::class), $this->createMock(AppAfterSaleRefundService::class), $outbox, $this->createMock(StockReservationInterface::class), $this->createMock(DomainOrderService::class), $this->transactions(), $events, new NullLogger(), $this->createMock(AppPaymentReconciliationService::class));
    }
}
