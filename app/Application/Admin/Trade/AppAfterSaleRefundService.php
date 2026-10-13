<?php

declare(strict_types=1);

namespace App\Application\Admin\Trade;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Trade\AfterSale\Service\DomainAfterSaleRefundCallbackService;
use App\Domain\Trade\AfterSale\Service\DomainAfterSaleRefundService;
use App\Domain\Trade\Order\Repository\OrderPaymentRefundRepository;
use App\Domain\Trade\Payment\Contract\PaymentGatewayInterface;
use App\Domain\Trade\Shared\Contract\TransactionManagerInterface;
use App\Infrastructure\Service\Pay\WechatPayConfigResolver;
use Psr\Log\LoggerInterface;

final class AppAfterSaleRefundService
{
    public function __construct(
        private readonly DomainAfterSaleRefundService $refunds,
        private readonly DomainAfterSaleRefundCallbackService $callbacks,
        private readonly OrderPaymentRefundRepository $repository,
        private readonly PaymentGatewayInterface $gateway,
        private readonly DomainMallSettingService $settings,
        private readonly WechatPayConfigResolver $configResolver,
        private readonly TransactionManagerInterface $transactions,
        private readonly LoggerInterface $logger,
    ) {}

    public function refund(int $afterSaleId, int $operatorId, string $operatorName): void
    {
        $refund = $this->transactions->run(fn () => $this->refunds->prepare($afterSaleId, $operatorId, $operatorName));
        $this->process($refund->refund_no);
    }

    /** 申请已经持久化；网络超时与处理失败均用原退款号恢复，不新建退款号。 */
    public function process(string $refundNo): void
    {
        $refund = $this->repository->findByRefundNo($refundNo);
        if ($refund === null || in_array($refund->status, ['success', 'failed'], true)) {
            return;
        }
        $extra = $refund->extra_data;
        $method = (string) ($extra['payment_method'] ?? 'wechat');
        $payload = [
            'out_trade_no' => $refund->order_no, 'out_refund_no' => $refundNo,
            'amount' => ['refund' => (int) $refund->refund_amount, 'total' => (int) ($extra['paid_amount'] ?? 0)],
        ];
        try {
            if ($method === 'balance') {
                $response = $payload + ['status' => 'SUCCESS'];
            } else {
                $config = $this->configResolver->resolve($this->settings->payment()->wechatConfig());
                $response = $refund->status === 'requested' ? ['status' => 'NOT_FOUND'] : $this->gateway->queryRefund($refundNo, $config);
                if (($response['status'] ?? '') === 'NOT_FOUND') {
                    $payload['reason'] = $refund->refund_reason;
                    $payload['amount']['currency'] = 'CNY';
                    if (! empty($config['refund_notify_url'])) {
                        $payload['notify_url'] = $config['refund_notify_url'];
                    }
                    $response = $this->gateway->refund($payload, $config);
                }
            }
            $this->transactions->run(fn () => $this->callbacks->complete($response, $method));
        } catch (\Throwable $e) {
            // 用锁避免覆盖并发成功回调；已受理或结果未知都必须继续按原号查询。
            $this->transactions->run(function () use ($refundNo, $e): void {
                $current = $this->repository->findForUpdate($refundNo);
                if ($current !== null && ! in_array($current->status, ['success', 'failed'], true)) {
                    $this->repository->updateByRefundNo($refundNo, ['status' => 'unknown', 'remark' => mb_substr($e->getMessage(), 0, 500)]);
                }
            });
            $this->logger->warning('退款结果待确认', ['refund_no' => $refundNo, 'error' => $e->getMessage()]);
        }
    }
}
