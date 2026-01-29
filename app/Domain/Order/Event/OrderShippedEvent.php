<?php

declare(strict_types=1);

namespace App\Domain\Order\Event;

use App\Domain\Order\Entity\OrderEntity;
use App\Domain\Order\Entity\OrderShipEntity;

final class OrderShippedEvent
{
    public function __construct(
        public readonly OrderEntity $order,
        public readonly OrderShipEntity $command
    ) {}
}
