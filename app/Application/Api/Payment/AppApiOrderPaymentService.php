<?php

declare(strict_types=1);

namespace App\Application\Api\Payment;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Member\Api\Command\DomainApiMemberCommandService;
use App\Domain\Trade\Payment\Contract\PaymentGatewayInterface;
use App\Domain\Trade\Payment\DomainPayService;
use App\Domain\Trade\Shared\Contract\TradeOutboxInterface;
use App\Domain\Trade\Shared\Contract\TransactionManagerInterface;
use App\Infrastructure\Service\Pay\WechatPayConfigResolver;

final class AppApiOrderPaymentService
{
    public function __construct(
        private readonly DomainApiMemberCommandService $members,
        private readonly DomainMallSettingService $settings,
        private readonly DomainPayService $payments,
        private readonly PaymentGatewayInterface $gateway,
        private readonly WechatPayConfigResolver $configResolver,
        private readonly TransactionManagerInterface $transactions,
        private readonly TradeOutboxInterface $outbox,
    ) {}

    public function payment(int $memberId, array $payload): array
    {
        $setting = $this->settings->payment();
        $orderNo = (string) $payload['order_no'];
        if ($payload['pay_method'] === 'balance') {
            $this->transactions->run(function () use ($orderNo, $memberId, $setting): void {
                $order = $this->payments->payByBalance($orderNo, $memberId, $setting);
                if ($order !== null) {
                    $this->outbox->append('paid:' . $orderNo, 'order.paid', [
                        'member_id' => $memberId, 'order_no' => $orderNo,
                        'order_id' => $order->getId(), 'amount' => $order->getPayAmount(),
                    ]);
                }
            });
            return ['is_paid' => true];
        }
        if ($payload['pay_method'] !== 'wechat') {
            throw new \InvalidArgumentException('不支持的支付方式');
        }
        $member = $this->members->getEntity($memberId);
        $params = $this->transactions->run(fn () => $this->payments->prepareWechat($orderNo, $member, $setting));
        return $this->gateway->pay($params, $this->configResolver->resolve($setting->wechatConfig()));
    }
}
