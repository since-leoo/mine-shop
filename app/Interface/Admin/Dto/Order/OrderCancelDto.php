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

namespace App\Interface\Admin\Dto\Order;

use App\Domain\Trade\Order\Contract\OrderCancelInput;

final class OrderCancelDto implements OrderCancelInput
{
    public int $order_id = 0;

    public string $reason = '';

    public int $operator_id = 0;

    public string $operator_name = '';

    public function getOrderId(): int
    {
        return $this->order_id;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getOperatorId(): int
    {
        return $this->operator_id;
    }

    public function getOperatorName(): string
    {
        return $this->operator_name;
    }
}
