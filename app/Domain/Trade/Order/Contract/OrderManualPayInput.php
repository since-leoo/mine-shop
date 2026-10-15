<?php

declare(strict_types=1);

namespace App\Domain\Trade\Order\Contract;

interface OrderManualPayInput
{
    public function getOrderId(): int;

    public function getRemark(): string;

    public function getOperatorId(): int;

    public function getOperatorName(): string;
}
