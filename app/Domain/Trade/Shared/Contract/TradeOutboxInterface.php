<?php

declare(strict_types=1);

namespace App\Domain\Trade\Shared\Contract;

interface TradeOutboxInterface
{
    /** 与业务写入使用同一数据库事务；业务键唯一，重复写入不重置已完成任务。 */
    public function append(string $key, string $type, array $payload): void;
}
