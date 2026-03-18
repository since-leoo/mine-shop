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

namespace App\Domain\Trade\AfterSale\Event;

final class AfterSaleRefundSucceeded
{
    public function __construct(
        public readonly int $afterSaleId,
        public readonly int $orderId,
        public readonly int $memberId,
        public readonly int $refundAmount,
        public readonly int $paymentId,
        public readonly string $paymentNo,
        public readonly string $paymentMethod,
        public readonly string $refundNo,
        public readonly int $operatorId,
        public readonly string $operatorName,
    ) {}
}
