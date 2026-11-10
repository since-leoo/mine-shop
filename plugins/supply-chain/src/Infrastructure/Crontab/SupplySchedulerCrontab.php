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

namespace Plugin\SupplyChain\Infrastructure\Crontab;

use Hyperf\Crontab\Annotation\Crontab;
use Plugin\SupplyChain\Application\Commad\AppSupplyScheduler;
use Plugin\SupplyChain\Domain\ValueObject\SafeLogVo;
use Psr\Log\LoggerInterface;

#[Crontab(name: 'supply-chain-dispatch', rule: '* * * * *', callback: 'execute', memo: '供应链任务投递、租约恢复和消息同步', enable: true)]
final class SupplySchedulerCrontab
{
    public function __construct(private readonly AppSupplyScheduler $scheduler, private readonly LoggerInterface $logger) {}

    public function execute(): void
    {
        try {
            $this->scheduler->tick();
        } catch (\Throwable $error) {
            $this->logger->error('供应链调度失败', ['reason' => SafeLogVo::error($error)]);
        }
    }
}
