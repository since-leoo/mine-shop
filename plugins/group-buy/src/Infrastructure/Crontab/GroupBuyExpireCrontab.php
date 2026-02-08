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

namespace Plugin\Since\GroupBuy\Infrastructure\Crontab;

use Hyperf\Crontab\Annotation\Crontab;
use Plugin\Since\GroupBuy\Domain\Service\DomainGroupBuyLifecycleService;
use Psr\Log\LoggerInterface;

#[Crontab(
    name: 'GroupBuyExpire',
    rule: '*/5 * * * *',
    callback: 'execute',
    memo: '拼团超时自动取消',
    enable: true
)]
class GroupBuyExpireCrontab
{
    public function __construct(
        private readonly DomainGroupBuyLifecycleService $groupService,
        private readonly LoggerInterface $logger
    ) {}

    public function execute(): void
    {
        try {
            $count = $this->groupService->cancelExpiredGroups();
            if ($count > 0) {
                $this->logger->info(\sprintf('[GroupBuyExpire] 已取消 %d 个超时拼团组', $count));
            }
        } catch (\Throwable $throwable) {
            $this->logger->error('[GroupBuyExpire] 超时取消失败：' . $throwable->getMessage());
        }
    }
}
