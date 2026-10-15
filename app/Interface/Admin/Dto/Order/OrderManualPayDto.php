<?php

declare(strict_types=1);

namespace App\Interface\Admin\Dto\Order;

use App\Domain\Trade\Order\Contract\OrderManualPayInput;

/** 认款命令及订单日志数据；不直接用于订单持久化。 */
final class OrderManualPayDto implements OrderManualPayInput
{
    public int $order_id = 0;

    public string $remark = '';

    public int $operator_id = 0;

    public string $operator_name = '';

    public function getOrderId(): int
    {
        return $this->order_id;
    }

    public function getRemark(): string
    {
        return $this->remark;
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
