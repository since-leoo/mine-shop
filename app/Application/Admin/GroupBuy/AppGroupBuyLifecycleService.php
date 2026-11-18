<?php

declare(strict_types=1);

namespace App\Application\Admin\GroupBuy;

use App\Application\Api\Order\AppOrderCancellationService;
use App\Domain\Trade\GroupBuy\Service\DomainGroupBuyLifecycleService;
use App\Domain\Trade\GroupBuy\Service\DomainGroupBuyRefundService;
use App\Domain\Trade\Shared\Contract\TransactionManagerInterface;
use Psr\Log\LoggerInterface;

final class AppGroupBuyLifecycleService
{
    public function __construct(
        private readonly DomainGroupBuyLifecycleService $groups,
        private readonly DomainGroupBuyRefundService $refunds,
        private readonly AppOrderCancellationService $cancellation,
        private readonly TransactionManagerInterface $transactions,
        private readonly LoggerInterface $logger,
    ) {}

    public function onOrderPaid(int $orderId): void
    {
        $this->transactions->run(function () use ($orderId): void {
            if ($this->groups->onOrderPaid($orderId) !== null) {
                $this->refunds->prepare($orderId);
            }
        });
    }

    public function onOrderCancelled(int $orderId): void
    {
        $this->transactions->run(fn () => $this->groups->onOrderCancelled($orderId));
    }

    public function cancelExpiredGroups(): int
    {
        $count = 0;
        foreach ($this->groups->expiredGroups() as $groupNo) {
            try {
                $processed = $this->transactions->run(function () use ($groupNo): bool {
                    $ids = $this->groups->expireGroup($groupNo);
                    foreach ($ids as $id) {
                        $unpaidOrder = $this->refunds->prepare($id);
                        if ($unpaidOrder !== null) {
                            $this->cancellation->cancel($unpaidOrder, reason: '拼团超时未成团');
                        }
                    }
                    return $ids !== [];
                });
                $count += (int) $processed;
            } catch (\Throwable $error) {
                $this->logger->error('拼团超时处理待重试', ['group_no' => $groupNo, 'error' => $error->getMessage()]);
            }
        }
        return $count;
    }
}
