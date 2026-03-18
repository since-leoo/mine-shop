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

namespace App\Domain\Trade\AfterSale\Service;

use App\Domain\Trade\AfterSale\Entity\AfterSaleEntity;
use App\Domain\Trade\AfterSale\Event\AfterSaleRefundSucceeded;
use App\Domain\Trade\Order\Repository\OrderPaymentRefundRepository;
use App\Domain\Trade\Order\Repository\OrderPaymentRepository;
use App\Domain\Trade\Payment\Enum\PayType;
use App\Infrastructure\Service\Pay\YsdPayService;
use Carbon\Carbon;
use Hyperf\Stringable\Str;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;
use Throwable;

class DomainAfterSaleRefundService
{
    public function __construct(
        private readonly OrderPaymentRepository $paymentRepository,
        private readonly OrderPaymentRefundRepository $paymentRefundRepository,
        private readonly YsdPayService $payService,
        private readonly EventDispatcherInterface $dispatcher,
    ) {}

    public function refund(AfterSaleEntity $afterSale, int $operatorId, string $operatorName): string
    {
        $payment = $this->paymentRepository->findByOrderId($afterSale->getOrderId());
        if ($payment === null) {
            throw new RuntimeException('订单支付记录不存在');
        }

        $refundNo = $this->generateRefundNo();
        $refundAmount = $afterSale->getRefundAmount();

        $this->paymentRefundRepository->create([
            'refund_no' => $refundNo,
            'payment_id' => $payment->id,
            'payment_no' => $payment->payment_no,
            'order_id' => $payment->order_id,
            'order_no' => $payment->order_no,
            'member_id' => $payment->member_id,
            'refund_amount' => $refundAmount,
            'refund_reason' => $afterSale->getReason(),
            'status' => 'refunding',
            'operator_type' => 'admin',
            'operator_id' => $operatorId,
            'operator_name' => $operatorName,
            'remark' => '后台确认退款',
            'extra_data' => [
                'after_sale_id' => $afterSale->getId(),
                'after_sale_no' => $afterSale->getAfterSaleNo(),
            ],
        ]);

        if ($payment->payment_method === PayType::BALANCE->value) {
            $this->paymentRefundRepository->updateByRefundNo($refundNo, [
                'status' => 'success',
                'processed_at' => Carbon::now()->toDateTimeString(),
            ]);

            $this->dispatchRefundSucceeded($afterSale, $payment, $refundAmount, $refundNo, $operatorId, $operatorName);

            return 'success';
        }

        if ((string) $payment->payment_method !== PayType::WECHAT->value) {
            throw new RuntimeException('暂不支持该支付方式的原路退款');
        }

        return $this->refundByWechat($afterSale, $payment, $refundNo, $refundAmount);
    }

    private function refundByWechat(AfterSaleEntity $afterSale, object $payment, string $refundNo, int $refundAmount): string
    {
        $config = config('pay.default.wechat', []);
        $notifyUrl = (string) ($config['refund_notify_url'] ?? $config['notify_url'] ?? '');

        $payload = [
            'out_trade_no' => (string) $payment->order_no,
            'out_refund_no' => $refundNo,
            'reason' => $afterSale->getReason(),
            'amount' => [
                'refund' => $refundAmount,
                'total' => (int) $payment->paid_amount,
                'currency' => 'CNY',
            ],
        ];

        if ($notifyUrl !== '') {
            $payload['notify_url'] = $notifyUrl;
        }

        $response = $this->payService->refund($payload, $config, 'mini');
        $status = strtoupper((string) ($response['status'] ?? ''));

        if ($status === 'SUCCESS' || $status === 'PROCESSING') {
            $this->paymentRefundRepository->updateByRefundNo($refundNo, [
                'status' => 'refunding',
                'third_party_refund_no' => $response['refund_id'] ?? null,
                'third_party_response' => $response,
            ]);

            return 'processing';
        }

        $this->paymentRefundRepository->updateByRefundNo($refundNo, [
            'status' => 'failed',
            'third_party_refund_no' => $response['refund_id'] ?? null,
            'third_party_response' => $response,
            'remark' => (string) ($response['message'] ?? '原路退款失败'),
        ]);

        throw new RuntimeException((string) ($response['message'] ?? '原路退款失败'));
    }

    private function dispatchRefundSucceeded(AfterSaleEntity $afterSale, object $payment, int $refundAmount, string $refundNo, int $operatorId, string $operatorName): void
    {
        $this->dispatcher->dispatch(new AfterSaleRefundSucceeded(
            $afterSale->getId(),
            $afterSale->getOrderId(),
            $afterSale->getMemberId(),
            $refundAmount,
            (int) $payment->id,
            (string) $payment->payment_no,
            (string) $payment->payment_method,
            $refundNo,
            $operatorId,
            $operatorName,
        ));
    }

    private function generateRefundNo(): string
    {
        return 'REF' . date('YmdHis') . Str::upper(Str::random(6));
    }
}
