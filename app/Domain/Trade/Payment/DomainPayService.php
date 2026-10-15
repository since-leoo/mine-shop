<?php

declare(strict_types=1);

namespace App\Domain\Trade\Payment;

use App\Domain\Infrastructure\SystemSetting\ValueObject\PaymentSetting;
use App\Domain\Member\Entity\MemberEntity;
use App\Domain\Member\Repository\MemberWalletTransactionRepository;
use App\Domain\Member\Service\DomainMemberWalletService;
use App\Domain\Trade\Order\Contract\OrderManualPayInput;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Repository\OrderPaymentRepository;
use App\Domain\Trade\Order\Service\DomainOrderPaymentService;
use App\Domain\Trade\Order\Service\DomainOrderService;
use App\Domain\Trade\Payment\ValueObject\PaymentPolicy;

/** 无请求级可变成员；所有命令由应用层包在事务中，统一先锁订单再锁钱包。 */
class DomainPayService
{
    public function __construct(
        private readonly DomainOrderService $orders,
        private readonly DomainOrderPaymentService $payments,
        private readonly OrderPaymentRepository $paymentRepository,
        private readonly DomainMemberWalletService $wallets,
        private readonly MemberWalletTransactionRepository $ledger,
        private readonly PaymentPolicy $policy,
        private readonly \App\Domain\Trade\Order\Repository\OrderPaymentRefundRepository $refunds,
    ) {}

    /** 与线上支付、取消共用订单行锁，必须在应用事务中调用。 */
    public function payManually(OrderManualPayInput $input): ?OrderEntity
    {
        $order = $this->orders->getLockedEntityById($input->getOrderId());
        $records = $this->paymentRepository->findAllForOrderForUpdate($order->getId());
        if ($order->getPayStatus() === 'paid' && $order->getPayMethod() === 'manual'
            && in_array($order->getStatus(), ['paid', 'partial_shipped', 'shipped', 'completed'], true)) {
            foreach ($records as $record) {
                if ($record->payment_method === 'manual' && $record->status === 'paid'
                    && $record->payment_no === $order->getPayNo()
                    && (int) $record->paid_amount === $order->getPayAmount()) {
                    return null;
                }
            }
        }

        $this->policy->assertManualPayable($order);
        foreach ($records as $record) {
            if (in_array($record->status, ['paid', 'refunded'], true) || (int) $record->paid_amount > 0) {
                throw new \DomainException('订单已有收款记录，请先核实支付情况');
            }
            if ($record->status === 'pending') {
                throw new \DomainException('订单存在待确认的支付流水，请先核实原支付渠道状态后再人工认款');
            }
        }

        $payment = $this->payments->create(
            $order->getId(), $order->getOrderNo(), $order->getMemberId(), 'manual', $order->getPayAmount(),
        );
        $log = $order->confirmManualPayment($input, $payment->payment_no);
        $this->payments->markPaid($payment->payment_no, $order->getPayAmount());
        $this->orders->update($order);
        $this->orders->recordLog($order->getId(), $log);

        return $order;
    }

    public function prepareWechat(string $orderNo, MemberEntity $member, PaymentSetting $setting): array
    {
        if (! $setting->wechatEnabled()) {
            throw new \DomainException('微信支付未启用');
        }
        $order = $this->orders->getLockedEntity($orderNo);
        $payload = $this->policy->wechatPayload($order, $member);
        $payment = $this->paymentRepository->findForOrder($order->getId(), 'wechat');
        if ($payment === null) {
            $this->payments->create($order->getId(), $orderNo, $member->getId(), 'wechat', $order->getPayAmount());
        }
        $order->setPayMethod('wechat');
        $this->orders->update($order);
        return $payload;
    }

    public function payByBalance(string $orderNo, int $memberId, PaymentSetting $setting): ?OrderEntity
    {
        $order = $this->orders->getLockedEntity($orderNo);
        $this->policy->assertOwner($order, $memberId);
        if ($order->getPayStatus() === 'paid') {
            return null;
        }
        $this->policy->assertPayable($order);
        if ($order->getPayMethod() === 'wechat') {
            throw new \DomainException('已发起微信支付，请完成或取消原支付订单');
        }
        // 读取、余额计算、日限额查询和落账均发生在钱包行锁内。
        $wallet = $this->wallets->getEntityForUpdate($memberId, 'balance');
        $this->policy->assertBalance($setting, $order->getPayAmount(), $this->paymentRepository->sumBalancePaidToday($memberId));
        if ($wallet->getStatus() !== 'active' || $wallet->getBalance() - $wallet->getFrozenBalance() < $order->getPayAmount()) {
            throw new \DomainException('钱包被冻结或可用余额不足');
        }
        $wallet->setChangeBalance(-$order->getPayAmount());
        $wallet->changeBalance();
        $payment = $this->payments->create($order->getId(), $orderNo, $memberId, 'balance', $order->getPayAmount());
        $order->setPayMethod('balance');
        $order->setPayNo($payment->payment_no);
        $order->markPaid();
        $this->wallets->saveEntity($wallet);
        $this->orders->update($order);
        $this->payments->markPaid($payment->payment_no, $order->getPayAmount(), $payment->payment_no);
        $this->ledger->create([
            'transaction_no' => 'OP' . substr(hash('sha256', $order->getOrderNo()), 0, 30), 'wallet_id' => $wallet->getId(),
            'member_id' => $memberId, 'wallet_type' => 'balance', 'type' => 'adjust_out',
            'amount' => $order->getPayAmount(), 'balance_before' => $wallet->getBeforeBalance(),
            'balance_after' => $wallet->getAfterBalance(), 'source' => 'consume',
            'related_type' => 'order', 'related_id' => $order->getId(),
            'description' => '订单余额支付', 'remark' => $orderNo,
            'operator_type' => 'member', 'operator_id' => $memberId,
        ]);
        return $order;
    }

    public function notify(array $payload, array $config): ?OrderEntity
    {
        $orderNo = (string) ($payload['out_trade_no'] ?? '');
        if ($orderNo === '') {
            throw new \InvalidArgumentException('支付通知缺少订单号');
        }
        if (($payload['trade_state'] ?? '') !== 'SUCCESS') {
            return null;
        }
        $order = $this->orders->getLockedEntity($orderNo);
        $amount = $this->policy->validateWechatSuccess($order, $payload, $config);
        if ($order->getPayStatus() === 'paid' || $order->getPayStatus() === 'refunded') {
            if ($order->getPayNo() !== $payload['transaction_id']) {
                throw new \DomainException('订单已由另一笔支付完成，需对账');
            }
            return null;
        }
        // 已过期但未取消的订单仍须接收实际成功的支付；关单会与这里竞争同一行锁。
        $order->setPayMethod('wechat');
        $order->setPayNo($payload['transaction_id']);
        $cancelled = $order->getStatus() === 'cancelled';
        if ($cancelled) {
            // 兼容旧关单或极晚渠道通知：保留取消状态，持久化原路退款，不重新占库存。
            $order->setPayStatus('paid');
        } else {
            $order->markPaid();
        }
        $payment = $this->paymentRepository->findForOrder($order->getId(), 'wechat');
        if ($payment === null || (int) $payment->payment_amount !== $amount) {
            throw new \DomainException('支付记录不存在或金额不匹配');
        }
        $this->payments->markPaid($payment->payment_no, $amount, $payload['transaction_id'], $payload);
        $this->orders->update($order);
        if ($cancelled) {
            $this->refunds->create([
                'refund_no' => 'LATE' . substr(hash('sha256', $orderNo), 0, 32),
                'payment_id' => $payment->id, 'payment_no' => $payment->payment_no,
                'order_id' => $order->getId(), 'order_no' => $orderNo, 'member_id' => $order->getMemberId(),
                'refund_amount' => $amount, 'refund_reason' => '取消订单收到支付，自动原路退款', 'status' => 'requested',
                'extra_data' => ['after_sale_id' => 0, 'payment_method' => 'wechat', 'paid_amount' => $amount, 'late_payment' => true],
            ]);
            return null;
        }
        return $order;
    }
}
