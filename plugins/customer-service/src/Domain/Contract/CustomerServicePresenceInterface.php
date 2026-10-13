<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Domain\Contract;

/** 在线租约边界；断开一个连接不能使同一坐席的其他连接离线。 */
interface CustomerServicePresenceInterface
{
    public function refresh(string $principalType, int $principalId, string $connectionId): void;

    public function disconnect(string $principalType, int $principalId, string $connectionId): void;

    public function isOnline(int $agentId): bool;
}
