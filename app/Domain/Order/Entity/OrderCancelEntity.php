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

namespace App\Domain\Order\Entity;

final class OrderCancelEntity
{
    private int $orderId = 0;

    private int $userId = 0;

    private string $reason = '';

    private int $operatorId = 0;

    private string $operatorName = '';

    public function setOrderId(int $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function setReason(string $reason): void
    {
        $this->reason = $reason;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setUserId(int $userId)
    {
        $this->userId = $userId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setOperatorId(int $param)
    {
        $this->operatorId = $param;
    }

    public function setOperatorName(string $param)
    {
        $this->operatorName = $param;
    }

    public function getOperatorId(): int
    {
        return $this->operatorId;
    }

    public function getOperatorName(): string
    {
        return $this->operatorName;
    }

    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'reason' => $this->reason,
        ];
    }
}
