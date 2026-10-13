<?php

declare(strict_types=1);

namespace App\Domain\Trade\Shared\Contract;

interface TradeWebhookInterface
{
    /** 实现必须抛出可重试异常，不得影响核心财务事务。 */
    public function dispatch(string $event, array $payload): void;
}
