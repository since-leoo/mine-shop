<?php

declare(strict_types=1);

namespace App\Domain\Trade\Order\Contract;

interface OrderJobQueueInterface
{
    public function push(string $tradeNo): bool;
}
