<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Domain\Contract;

/** 连接票据存储边界；consume 必须原子取出并删除，且遵守过期时间。 */
interface CustomerServiceTicketStoreInterface
{
    public function put(string $ticket, array $principal, int $expiresIn): void;

    public function consume(string $ticket): ?array;
}
