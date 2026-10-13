<?php

declare(strict_types=1);

namespace App\Infrastructure\Service\Trade;

use App\Domain\Trade\Shared\Contract\TradeWebhookInterface;

final class NullTradeWebhook implements TradeWebhookInterface
{
    public function dispatch(string $event, array $payload): void {}
}
