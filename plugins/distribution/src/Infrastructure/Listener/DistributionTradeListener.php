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

namespace Plugin\Distribution\Infrastructure\Listener;

use App\Domain\Member\Event\OrderPaidForMember;
use App\Domain\Trade\AfterSale\Event\AfterSaleRefundSucceeded;
use App\Domain\Trade\Order\Event\OrderCompletedEvent;
use App\Domain\Trade\Order\Event\OrderPersisted;
use App\Domain\Trade\Order\Event\OrderSubmissionPreparing;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Plugin\Distribution\Application\AppDistributionTradeService;

#[Listener]
final class DistributionTradeListener implements ListenerInterface
{
    public function __construct(private readonly AppDistributionTradeService $trade) {}

    public function listen(): array
    {
        return [OrderSubmissionPreparing::class, OrderPersisted::class, OrderPaidForMember::class, AfterSaleRefundSucceeded::class, OrderCompletedEvent::class];
    }

    public function process(object $event): void
    {
        if ($event instanceof OrderSubmissionPreparing) {
            $event->payload['distribution'] = $this->trade->snapshot($event->memberId, $event->payload);
        } elseif ($event instanceof OrderPersisted) {
            $this->trade->created($event->orderNo, $event->payload['distribution'] ?? []);
        } elseif ($event instanceof OrderPaidForMember) {
            $this->trade->paid($event->orderId);
        } elseif ($event instanceof AfterSaleRefundSucceeded) {
            $this->trade->refunded($event->orderId);
        } elseif ($event instanceof OrderCompletedEvent) {
            $this->trade->recordCompletion($event->orderId, $event->completedAt);
        }
    }
}
