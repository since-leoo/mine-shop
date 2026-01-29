<?php

declare(strict_types=1);

namespace App\Domain\Order\Event;

use App\Domain\Order\Entity\OrderCancelEntity;
use App\Domain\Order\Entity\OrderEntity;

final class OrderCancelledEvent
{
    public function __construct(
        public readonly OrderEntity $order,
        public readonly OrderCancelEntity $command
    ) {}
}
