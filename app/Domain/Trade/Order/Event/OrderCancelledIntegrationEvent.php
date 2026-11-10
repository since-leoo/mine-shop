<?php

declare(strict_types=1);

namespace App\Domain\Trade\Order\Event;

final readonly class OrderCancelledIntegrationEvent
{
    public function __construct(public string $orderNo, public string $reason) {}
}
