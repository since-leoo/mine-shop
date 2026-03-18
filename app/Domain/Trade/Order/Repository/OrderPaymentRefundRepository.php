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
        return (bool) OrderPaymentRefund::query()
            ->where('refund_no', $refundNo)
            ->update($data);
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
}
