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

namespace App\Domain\Trade\Order\Repository;

use App\Infrastructure\Model\Order\OrderPaymentRefund;

class OrderPaymentRefundRepository
{
    public function create(array $data): OrderPaymentRefund
    {
        return OrderPaymentRefund::query()->create($data);
    }

    public function findByRefundNo(string $refundNo): ?OrderPaymentRefund
    {
        return OrderPaymentRefund::query()->where('refund_no', $refundNo)->first();
    }

    public function updateByRefundNo(string $refundNo, array $data): bool
    {
        $record = $this->findByRefundNo($refundNo);
        return $record !== null && $record->fill($data)->save();
    }

    public function findLatestByAfterSaleId(int $afterSaleId): ?OrderPaymentRefund
    {
        if ($afterSaleId <= 0) {
            return null;
        }

        return OrderPaymentRefund::query()
            ->where('extra_data->after_sale_id', $afterSaleId)
            ->orderByDesc('id')
            ->first();
    }

    public function findForUpdate(string $refundNo): ?OrderPaymentRefund
    {
        return OrderPaymentRefund::query()->where('refund_no', $refundNo)->lockForUpdate()->first();
    }

    public function outstandingAmount(int $paymentId): int
    {
        return (int) OrderPaymentRefund::query()->where('payment_id', $paymentId)
            ->whereIn('status', ['requested', 'refunding', 'unknown'])->sum('refund_amount');
    }

    public function recoverable(int $limit = 100): array
    {
        return OrderPaymentRefund::query()->whereIn('status', ['requested', 'refunding', 'unknown'])
            ->orderBy('updated_at')->limit($limit)->get()->all();
    }
}
