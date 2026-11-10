<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace Plugin\Distribution\Infrastructure\Crontab;

use Hyperf\Crontab\Annotation\Crontab;
use Plugin\Distribution\Application\AppDistributionTradeService;
use Psr\Log\LoggerInterface;

#[Crontab(name: 'distribution-settlement', rule: '* * * * *', callback: 'execute', memo: '分销历史订单到期结算，业务停用不停止', enable: true)]
final class DistributionSettlementCrontab
{
    public function __construct(private readonly AppDistributionTradeService $trade, private readonly LoggerInterface $logger) {}

    public function execute(): void
    {
        try {
            foreach ($this->trade->settleDue() as $failure) {
                $this->logger->error('订单佣金结算失败，等待重试', $failure);
            }
        } catch (\Throwable $e) {
            $this->logger->error('佣金结算失败，等待重试', ['error' => $e->getMessage()]);
        }
    }
}
