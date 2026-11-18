<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Trade;

use App\Application\Admin\Trade\AppAfterSaleRefundService;
use App\Application\Api\Order\AppOrderSubmissionService;
use App\Application\Api\Order\AppTradeRecoveryService;
use App\Application\Api\Payment\AppPaymentReconciliationService;
use App\Domain\Member\Event\OrderPaidForMember;
use App\Domain\Trade\Order\Contract\OrderSubmissionStoreInterface;
use App\Domain\Trade\Order\Contract\StockReservationInterface;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Event\OrderCancelledEvent;
use App\Domain\Trade\Order\Event\OrderCancelledIntegrationEvent;
use App\Domain\Trade\Order\Event\OrderPaidIntegrationEvent;
use App\Domain\Trade\Order\Repository\OrderPaymentRefundRepository;
use App\Domain\Trade\Order\Service\DomainOrderService;
use App\Domain\Trade\Shared\Contract\TransactionManagerInterface;
use App\Domain\Trade\Shared\Repository\TradeOutboxRepository;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;

final class RecoveryDispatchTest extends TestCase
{
    public function testReplayKeepsEventOrderStockReleaseAndUnknownEventsPending(): void
    {
        $payloads = [
            ['order.paid', ['order_id' => 1, 'order_no' => 'PAID', 'member_id' => 7, 'amount' => 600]],
            ['order.cancelled', ['order_no' => 'CANCELLED', 'reason' => '取消', 'operator_id' => 7, 'operator_name' => '测试']],
            ['stock.release', ['trade_no' => 'RELEASE', 'items' => [['sku_id' => 9, 'quantity' => 2]], 'stock_key' => 'product:stock', 'legacy' => true]],
            ['unknown.event', []],
        ];
        $rows = [];
        foreach ($payloads as $index => [$type, $data]) {
            $rows[$index + 1] = (object) ['id' => $index + 1, 'event_type' => $type, 'payload' => json_encode($data, JSON_THROW_ON_ERROR), 'processed_at' => null];
        }
        $outbox = $this->createMock(TradeOutboxRepository::class);
        $outbox->method('pending')->willReturn(array_values($rows));
        $outbox->method('lock')->willReturnCallback(static fn (int $id) => $rows[$id]);
        $completed = [];
        $outbox->expects(self::exactly(3))->method('complete')->willReturnCallback(static function (int $id) use (&$completed): void { $completed[] = $id; });
        $outbox->expects(self::once())->method('recordFailure')->with(4, '未知交易事件：unknown.event');
        $seen = [];
        $events = $this->createMock(EventDispatcherInterface::class);
        $events->expects(self::exactly(4))->method('dispatch')->willReturnCallback(static function (object $event) use (&$seen): object {
            $seen[] = $event::class;
            return $event;
        });
        $orders = $this->createMock(DomainOrderService::class);
        $orders->expects(self::once())->method('getEntity')->with(0, 'CANCELLED')->willReturnCallback(static function () use (&$seen): OrderEntity {
            self::assertSame(OrderCancelledIntegrationEvent::class, end($seen), 'Reload the order after the integration listener has run.');
            return new OrderEntity();
        });
        $stock = $this->createMock(StockReservationInterface::class);
        $stock->expects(self::once())->method('release')->with('RELEASE', [['sku_id' => 9, 'quantity' => 2]], 'product:stock', true);
        $transactions = $this->createMock(TransactionManagerInterface::class);
        $transactions->method('run')->willReturnCallback(static fn (callable $callback) => $callback());
        $service = new AppTradeRecoveryService(
            $this->createMock(OrderSubmissionStoreInterface::class),
            $this->createMock(AppOrderSubmissionService::class),
            $this->createMock(OrderPaymentRefundRepository::class),
            $this->createMock(AppAfterSaleRefundService::class),
            $outbox, $stock, $orders, $transactions, $events, new NullLogger(),
            $this->createMock(AppPaymentReconciliationService::class),
        );
        $service->publish();
        self::assertSame([OrderPaidIntegrationEvent::class, OrderPaidForMember::class, OrderCancelledIntegrationEvent::class, OrderCancelledEvent::class], $seen);
        self::assertSame([1, 2, 3], $completed);
    }
}
