<?php

declare(strict_types=1);

namespace App\Domain\Trade\Order\Service;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Trade\Order\Contract\CouponServiceInterface;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Payment\Contract\PaymentGatewayInterface;
use App\Infrastructure\Service\Pay\WechatPayConfigResolver;

class DomainOrderCancellationService
{
    public function __construct(
        private readonly DomainOrderService $orders,
        private readonly CouponServiceInterface $coupons,
        private readonly PaymentGatewayInterface $gateway,
        private readonly DomainMallSettingService $settings,
        private readonly WechatPayConfigResolver $configResolver,
    ) {}

    /** 与支付使用同一订单行锁；必须在应用事务中执行。 */
    public function cancel(string $orderNo, ?int $memberId, bool $expiredOnly): ?OrderEntity
    {
        $order = $this->orders->getLockedEntity($orderNo);
        if ($memberId !== null && $order->getMemberId() !== $memberId) {
            throw new \DomainException('订单不存在');
        }
        if ($order->getStatus() === 'cancelled') {
            return null;
        }
        if ($expiredOnly && ($order->getStatus() !== 'pending' || ! $order->getExpireTime()?->isPast())) {
            return null;
        }
        $order->cancel();
        if ($order->getPayMethod() === 'wechat') {
            $config = $this->configResolver->resolve($this->settings->payment()->wechatConfig());
            $state = $this->gateway->query($orderNo, $config);
            if (($state['trade_state'] ?? '') === 'SUCCESS') {
                throw new \DomainException('渠道已支付，等待支付通知或对账');
            }
            if (! in_array($state['trade_state'] ?? '', ['CLOSED', 'REVOKED', 'PAYERROR'], true)) {
                // 不确定或关单失败时保留 pending，不先放回库存。
                $this->gateway->close($orderNo, $config);
            }
        }
        $this->orders->update($order);
        $this->coupons->releaseOrderCoupons($order->getId());
        return $order;
    }
}
