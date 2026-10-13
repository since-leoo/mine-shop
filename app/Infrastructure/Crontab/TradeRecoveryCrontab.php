<?php

declare(strict_types=1);

namespace App\Infrastructure\Crontab;

use App\Application\Api\Order\AppTradeRecoveryService;
use Hyperf\Crontab\Annotation\Crontab;

#[Crontab(name: 'trade-recovery', rule: '* * * * *', callback: 'execute', memo: '恢复订单、退款和交易事件', enable: true)]
final class TradeRecoveryCrontab
{
    public function __construct(private readonly AppTradeRecoveryService $recovery) {}

    public function execute(): void
    {
        $this->recovery->recover();
    }
}
