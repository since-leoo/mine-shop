<?php

declare(strict_types=1);

namespace App\Domain\Trade\AfterSale\Service;

use App\Domain\Member\Repository\MemberWalletTransactionRepository;
use App\Domain\Member\Service\DomainMemberWalletService;
use App\Domain\Trade\Order\Service\DomainOrderPaymentService;
use App\Domain\Trade\Order\Service\DomainOrderService;
use App\Infrastructure\Model\Order\OrderPaymentRefund;

/** 在订单、支付、退款行锁和同一数据库事务内落账。 */
class DomainRefundSettlementService
{
    public function __construct(
        private readonly DomainAfterSaleService $afterSales,
        private readonly DomainOrderPaymentService $payments,
        private readonly DomainOrderService $orders,
        private readonly DomainMemberWalletService $wallets,
        private readonly MemberWalletTransactionRepository $ledger,
    ) {}

    public function settle(OrderPaymentRefund $refund, string $method): void
    {
        $afterSaleId = (int) ($refund->extra_data['after_sale_id'] ?? 0);
        if ($afterSaleId > 0) {
            $afterSale = $this->afterSales->getLockedEntity($afterSaleId);
            $afterSale->markRefunded();
            $this->afterSales->saveEntity($afterSale);
        }
        $payment = $this->payments->addRefundAmount($refund->payment_no, (int) $refund->refund_amount);
        if ($payment?->status === 'refunded') {
            $order = $this->orders->getEntity((int) $refund->order_id);
            $order->setPayStatus('refunded');
            $order->setStatus('refunded');
            $this->orders->update($order);
        }
        if ($method === 'balance') {
            $wallet = $this->wallets->getEntityForUpdate((int) $refund->member_id, 'balance');
            $wallet->refundConsumption((int) $refund->refund_amount);
            $this->wallets->saveEntity($wallet);
            $this->ledger->create([
                'transaction_no' => 'RF' . substr(hash('sha256', $refund->refund_no), 0, 30),
                'wallet_id' => $wallet->getId(), 'member_id' => $refund->member_id, 'wallet_type' => 'balance',
                'type' => 'adjust_in', 'amount' => $refund->refund_amount,
                'balance_before' => $wallet->getBeforeBalance(), 'balance_after' => $wallet->getAfterBalance(),
                'source' => 'refund', 'related_type' => 'after_sale', 'related_id' => $afterSaleId,
                'description' => '订单退款', 'remark' => $refund->refund_no,
                'operator_type' => 'admin', 'operator_id' => $refund->operator_id,
            ]);
        }
    }
}
