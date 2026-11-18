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

namespace App\Application\Api\Order;

use App\Application\Admin\Trade\AppAfterSaleRefundService;

use App\Application\Api\Payment\AppPaymentReconciliationService;
use App\Domain\Catalog\Product\Event\ProductCreated;
use App\Domain\Catalog\Product\Event\ProductDeleted;
use App\Domain\Catalog\Product\Event\ProductUpdated;
use App\Domain\Catalog\Product\ValueObject\ProductChangeVo;
use App\Domain\Member\Event\OrderPaidForMember;
use App\Domain\Trade\AfterSale\Event\AfterSaleChangedIntegrationEvent;
use App\Domain\Trade\AfterSale\Event\AfterSaleRefundSucceeded;
use App\Domain\Trade\Order\Contract\OrderSubmissionStoreInterface;
use App\Domain\Trade\Order\Contract\StockReservationInterface;
use App\Domain\Trade\Order\Event\OrderCancelledEvent;
use App\Domain\Trade\Order\Event\OrderCancelledIntegrationEvent;
use App\Domain\Trade\Order\Event\OrderCompletedEvent;
use App\Domain\Trade\Order\Event\OrderCreatedEvent;
use App\Domain\Trade\Order\Event\OrderPaidIntegrationEvent;
use App\Domain\Trade\Order\Repository\OrderPaymentRefundRepository;
use App\Domain\Trade\Order\Service\DomainOrderService;
use App\Domain\Trade\Shared\Contract\TransactionManagerInterface;
use App\Domain\Trade\Shared\Repository\TradeOutboxRepository;
use App\Infrastructure\Error\OrderError;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

final class AppTradeRecoveryService
{
    public function __construct(
        private readonly OrderSubmissionStoreInterface $submissions,
        private readonly AppOrderSubmissionService $submissionService,
        private readonly OrderPaymentRefundRepository $refunds,
        private readonly AppAfterSaleRefundService $refundService,
        private readonly TradeOutboxRepository $outbox,
        private readonly StockReservationInterface $stock,
        private readonly DomainOrderService $orders,
        private readonly TransactionManagerInterface $transactions,
        private readonly EventDispatcherInterface $events,
        private readonly LoggerInterface $logger,
        private readonly AppPaymentReconciliationService $paymentRecovery,
    ) {}

    public function recover(): void
    {
        $this->paymentRecovery->recover();
        foreach ($this->submissions->pending() as $tradeNo) {
            try {
                $this->submissionService->process($tradeNo);
            } catch (\DomainException $e) {
                $this->submissionService->fail($tradeNo, $e);
            } catch (\Throwable $e) {
                $this->transactions->run(function () use ($tradeNo, $e): void {
                    $current = $this->submissions->lock($tradeNo);
                    if ($current !== null && \in_array($current['status'], ['pending', 'reserved'], true)) {
                        $this->submissions->updateStatus($tradeNo, $current['status'], $e->getMessage());
                    }
                });
                $this->logger->warning('订单恢复等待重试', ['trade_no' => $tradeNo, 'error' => $e->getMessage()]);
            }
        }
        foreach ($this->refunds->recoverable() as $refund) {
            try {
                $this->refundService->process($refund->refund_no);
            } catch (\Throwable $e) {
                $this->logger->warning('退款恢复等待重试', ['refund_no' => $refund->refund_no, 'error' => $e->getMessage()]);
            }
        }
        $this->publish();
    }

    public function publish(): void
    {
        foreach ($this->outbox->pending() as $item) {
            try {
                $this->transactions->run(function () use ($item): void {
                    $row = $this->outbox->lock((int) $item->id);
                    if ($row === null || $row->processed_at !== null) {
                        return;
                    }
                    $data = json_decode($row->payload, true, 512, \JSON_THROW_ON_ERROR);
                    // 监听器的数据库写入与完成标记共用事务；中途失败后整条事件仍可重放。
                    // Redis 补偿自身幂等，外部通知按至少一次投递由接收方去重。
                    $this->dispatch($row->event_type, $data);
                    $this->outbox->complete((int) $row->id);
                });
            } catch (\Throwable $e) {
                $this->outbox->recordFailure((int) $item->id, $e->getMessage());
            }
        }
    }

    private function dispatch(string $type, array $data): void
    {
        match ($type) {
            'catalog.product.created' => $this->events->dispatch(new ProductCreated(...$data)),
            'catalog.product.updated' => $this->events->dispatch(new ProductUpdated(...[
                ...$data, 'changes' => new ProductChangeVo(...$data['changes']),
            ])),
            'catalog.product.deleted' => $this->events->dispatch(new ProductDeleted(...$data)),
            'stock.release' => $this->stock->release($data['trade_no'], $data['items'], $data['stock_key'], (bool) ($data['legacy'] ?? false)),
            'after_sale.changed' => $this->events->dispatch(new AfterSaleChangedIntegrationEvent((int) $data['after_sale_id'], (string) $data['action'])),
            'order.completed' => $this->events->dispatch(new OrderCompletedEvent($data['order_id'], $data['order_no'], $data['member_id'], $data['completed_at'] ?? null)),
            'order.paid' => $this->dispatchOrderPaid($data),
            'order.created' => $this->events->dispatch(new OrderCreatedEvent($this->orders->getCreatedEventEntity($data['order_no'], (bool) $data['from_cart']))),
            'order.cancelled' => $this->dispatchOrderCancelled($data),
            'refund.succeeded' => $this->events->dispatch(new AfterSaleRefundSucceeded($data['after_sale_id'], $data['order_id'], $data['member_id'], $data['refund_amount'], $data['payment_id'], $data['payment_no'], $data['payment_method'], $data['refund_no'], $data['operator_id'], $data['operator_name'])),
            default => throw OrderError::UNKNOWN_TRADE_EVENT->exception([$type]),
        };
    }

    private function dispatchOrderPaid(array $data): void
    {
        $this->events->dispatch(new OrderPaidIntegrationEvent((int) $data['order_id'], (string) $data['order_no']));
        $this->events->dispatch(new OrderPaidForMember($data['member_id'], $data['order_no'], $data['amount'], $data['order_id']));
    }

    private function dispatchOrderCancelled(array $data): void
    {
        $this->events->dispatch(new OrderCancelledIntegrationEvent((string) $data['order_no'], (string) $data['reason']));
        $this->events->dispatch(new OrderCancelledEvent($this->orders->getEntity(orderNo: $data['order_no']), $data['reason'], $data['operator_id'], $data['operator_name']));
    }
}
