<?php

declare(strict_types=1);

namespace Plugin\Since\GroupBuy\Domain\Job;

use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;
use Plugin\Since\GroupBuy\Domain\Enum\GroupBuyStatus;
use Plugin\Since\GroupBuy\Domain\Service\DomainGroupBuyService;
use Plugin\Since\GroupBuy\Domain\Service\GroupBuyCacheService;
use Psr\Log\LoggerInterface;

class GroupBuyStartJob extends Job
{
    public int $maxAttempts = 3;

    public function __construct(protected int $groupBuyId) {}

    public function handle(): void
    {
        $container = ApplicationContext::getContainer();
        $groupBuyService = $container->get(DomainGroupBuyService::class);
        $logger = $container->get(LoggerInterface::class);

        try {
            $groupBuy = $groupBuyService->repository->findById($this->groupBuyId);
            if (! $groupBuy) {
                $logger->warning('GroupBuyStartJob: 活动不存在，跳过', ['group_buy_id' => $this->groupBuyId]);
                return;
            }
            if ($groupBuy->status !== GroupBuyStatus::PENDING->value) {
                $logger->info('GroupBuyStartJob: 活动状态非 pending，跳过', [
                    'group_buy_id' => $this->groupBuyId, 'current_status' => $groupBuy->status,
                ]);
                return;
            }
            $groupBuyService->start($this->groupBuyId);
            $container->get(GroupBuyCacheService::class)->warmStock($this->groupBuyId);
            $logger->info('GroupBuyStartJob: 活动已激活', ['group_buy_id' => $this->groupBuyId]);
        } catch (\Throwable $e) {
            $logger->error('GroupBuyStartJob: 执行失败', [
                'group_buy_id' => $this->groupBuyId, 'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
