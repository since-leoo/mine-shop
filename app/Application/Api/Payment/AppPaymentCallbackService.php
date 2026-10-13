<?php

declare(strict_types=1);

namespace App\Application\Api\Payment;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Trade\AfterSale\Service\DomainAfterSaleRefundCallbackService;
use App\Domain\Trade\Payment\DomainPayService;
use App\Domain\Trade\Shared\Contract\TradeOutboxInterface;
use App\Domain\Trade\Shared\Contract\TransactionManagerInterface;
use App\Infrastructure\Service\Pay\WechatPayConfigResolver;

final class AppPaymentCallbackService
{
    public function __construct(
        private readonly DomainPayService $payments,
        private readonly DomainAfterSaleRefundCallbackService $refunds,
        private readonly DomainMallSettingService $settings,
        private readonly WechatPayConfigResolver $configResolver,
        private readonly TransactionManagerInterface $transactions,
        private readonly TradeOutboxInterface $outbox,
    ) {}

    public function paid(array $payload): void
    {
        $config = $this->configResolver->resolve($this->settings->payment()->wechatConfig());
        $this->transactions->run(function () use ($payload, $config): void {
            $order = $this->payments->notify($payload, $config);
            if ($order !== null) {
                $this->outbox->append('paid:' . $order->getOrderNo(), 'order.paid', [
                    'member_id' => $order->getMemberId(), 'order_no' => $order->getOrderNo(),
                    'order_id' => $order->getId(), 'amount' => $order->getPayAmount(),
                ]);
            }
        });
    }

    public function refunded(array $payload): void
    {
        $config = $this->configResolver->resolve($this->settings->payment()->wechatConfig());
        if (($payload['mchid'] ?? '') !== (string) $config['mch_id']) {
            throw new \DomainException('退款通知商户不匹配');
        }
        $this->transactions->run(fn () => $this->refunds->handleWechatRefundCallback($payload));
    }
}
