<?php

declare(strict_types=1);

namespace App\Domain\Order\Event;

use App\Domain\Order\Entity\OrderEntity;

final class OrderCreatedEvent
{
    public function __construct(public readonly OrderEntity $order) {}
}
