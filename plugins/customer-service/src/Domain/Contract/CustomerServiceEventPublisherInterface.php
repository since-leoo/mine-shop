<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Domain\Contract;

/** 持久化事件到实时通道的边界；业务通过此接口发布，不依赖具体 Socket 实现。 */
interface CustomerServiceEventPublisherInterface
{
    public function publish(string $event, array $payload, ?string $principalType = null, ?int $principalId = null, array $context = []): void;
}
