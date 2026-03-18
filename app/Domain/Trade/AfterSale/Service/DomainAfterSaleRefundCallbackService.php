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

use App\Domain\Trade\AfterSale\Event\AfterSaleRefundSucceeded;
use App\Domain\Trade\Order\Repository\OrderPaymentRefundRepository;
use Carbon\Carbon;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;

class DomainAfterSaleRefundCallbackService
{
    public function __construct(
        private readonly OrderPaymentRefundRepository $paymentRefundRepository,
        private readonly EventDispatcherInterface $dispatcher,
    ) {}

    public function handleWechatRefundCallback(array $payload): void
    {
        $refundNo = (string) ($payload['out_refund_no'] ?? '');
        if ($refundNo === '') {
            throw new RuntimeException('退款回调缺少退款单号');
        }

        $refund = $this->paymentRefundRepository->findByRefundNo($refundNo);
        if ($refund === null) {
            throw new RuntimeException('退款记录不存在');
        }

        if ((string) $refund->status === 'success') {
            return;
        }

        $refundStatus = strtoupper((string) ($payload['refund_status'] ?? ''));
        if ($refundStatus !== 'SUCCESS') {
            $this->paymentRefundRepository->updateByRefundNo($refundNo, [
                'status' => 'failed',
                'third_party_refund_no' => $payload['refund_id'] ?? null,
                'third_party_response' => $payload,
                'remark' => (string) ($payload['refund_status'] ?? '退款失败'),
            ]);

            return;
        }

        $refundAmount = (int) (($payload['amount']['refund'] ?? null) ?: $refund->refund_amount);
        $this->paymentRefundRepository->updateByRefundNo($refundNo, [
            'status' => 'success',
            'third_party_refund_no' => $payload['refund_id'] ?? null,
            'third_party_response' => $payload,
            'processed_at' => (string) (($payload['success_time'] ?? null) ?: Carbon::now()->toDateTimeString()),
        ]);

        $extraData = is_array($refund->extra_data) ? $refund->extra_data : [];
        $this->dispatcher->dispatch(new AfterSaleRefundSucceeded(
            (int) ($extraData['after_sale_id'] ?? 0),
            (int) $refund->order_id,
            (int) $refund->member_id,
            $refundAmount,
            (int) $refund->payment_id,
            (string) $refund->payment_no,
            'wechat',
            $refundNo,
            (int) ($refund->operator_id ?? 0),
            (string) ($refund->operator_name ?? '系统'),
        ));
    }
}
