<?php

declare(strict_types=1);

namespace App\Domain\Trade\Order\Event;

/** 由交易 Outbox 在事务中至少一次发布；监听器只持久化本地任务，不调用外部 HTTP。 */
final readonly class OrderPaidIntegrationEvent
{
    public function __construct(public int $orderId, public string $orderNo) {}
}
