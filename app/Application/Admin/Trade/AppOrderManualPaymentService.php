<?php

declare(strict_types=1);

namespace App\Application\Admin\Trade;

use App\Domain\Trade\Order\Contract\OrderManualPayInput;
use App\Domain\Trade\Order\Service\DomainOrderService;
use App\Domain\Trade\Payment\DomainPayService;
use App\Domain\Trade\Shared\Contract\TradeOutboxInterface;
use App\Domain\Trade\Shared\Contract\TransactionManagerInterface;

final class AppOrderManualPaymentService
{
    public function __construct(
        private readonly DomainPayService $payments,
        private readonly DomainOrderService $orders,
        private readonly TransactionManagerInterface $transactions,
        private readonly TradeOutboxInterface $outbox,
    ) {}

    public function pay(OrderManualPayInput $input): array
    {
        $this->transactions->run(function () use ($input): void {
            $order = $this->payments->payManually($input);
            if ($order !== null) {
                $this->outbox->append('paid:' . $order->getOrderNo(), 'order.paid', [
                    'member_id' => $order->getMemberId(), 'order_no' => $order->getOrderNo(),
                    'order_id' => $order->getId(), 'amount' => $order->getPayAmount(),
                ]);
            }
        });

        return $this->orders->findDetail($input->getOrderId()) ?? [];
    }
}
