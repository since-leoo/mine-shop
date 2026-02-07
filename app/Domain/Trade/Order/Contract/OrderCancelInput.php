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

namespace App\Domain\Trade\Order\Contract;

/**
 * 订单取消输入契约接口.
 */
interface OrderCancelInput
{
    public function getOrderId(): int;

    public function getReason(): string;

    public function getOperatorId(): int;

    public function getOperatorName(): string;
}
