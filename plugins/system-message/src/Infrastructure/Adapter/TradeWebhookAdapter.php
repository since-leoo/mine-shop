<?php

declare(strict_types=1);

namespace Plugin\SystemMessage\Infrastructure\Adapter;

use App\Domain\Trade\Shared\Contract\TradeWebhookInterface;
use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Service\OutboundWebhookDispatcher;

final class TradeWebhookAdapter implements TradeWebhookInterface
{
    public function __construct(private readonly OutboundWebhookDispatcher $dispatcher) {}

    public function dispatch(string $event, array $payload): void
    {
        $this->dispatcher->dispatch($event, $payload);
    }
}
