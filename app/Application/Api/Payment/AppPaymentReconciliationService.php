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

namespace App\Application\Api\Payment;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Trade\Order\Repository\OrderPaymentRepository;
use App\Domain\Trade\Payment\Contract\PaymentGatewayInterface;
use App\Infrastructure\Service\Pay\WechatPayConfigResolver;
use Psr\Log\LoggerInterface;

final class AppPaymentReconciliationService
{
    public function __construct(
        private readonly OrderPaymentRepository $payments,
        private readonly AppPaymentCallbackService $callbacks,
        private readonly PaymentGatewayInterface $gateway,
        private readonly DomainMallSettingService $settings,
        private readonly WechatPayConfigResolver $configResolver,
        private readonly LoggerInterface $logger,
    ) {}

    /** 渠道查询在数据库事务外执行，成功结果交给统一回调事务落账。 */
    public function recover(): void
    {
        foreach ($this->payments->unreconciledWechat() as $payment) {
            try {
                $config = $this->configResolver->resolve($this->settings->payment()->wechatConfig());
                $state = $this->gateway->query($payment->order_no, $config);
                if (($state['trade_state'] ?? '') === 'SUCCESS') {
                    $this->callbacks->paid($state);
                } elseif (\in_array($state['trade_state'] ?? '', ['CLOSED', 'REVOKED', 'PAYERROR'], true)) {
                    $this->payments->closePendingFromChannel((int) $payment->id, $state['trade_state']);
                }
            } catch (\Throwable $e) {
                $this->logger->warning('支付查单待重试', ['order_no' => $payment->order_no, 'error' => $e->getMessage()]);
            } finally {
                $this->payments->touchReconciled((int) $payment->id);
            }
        }
    }
}
