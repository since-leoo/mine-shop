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

namespace App\Domain\Member\Event;

/**
 * 订单支付事件（面向会员积分/成长值）.
 */
final class OrderPaidForMember
{
    public function __construct(
        public readonly int $memberId,
        public readonly string $orderNo,
        public readonly int $payAmountCents,
    ) {}
}
