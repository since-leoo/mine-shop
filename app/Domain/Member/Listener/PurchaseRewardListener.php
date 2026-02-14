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

namespace App\Domain\Member\Listener;

use App\Domain\Member\Event\OrderPaidForMember;
use App\Domain\Member\Service\DomainMemberGrowthService;
use App\Domain\Member\Service\DomainMemberPointsService;
use Hyperf\Event\Contract\ListenerInterface;

/**
 * 消费返积分+成长值监听器：监听订单支付事件，发放消费返积分并增加成长值.
 */
final class PurchaseRewardListener implements ListenerInterface
{
    public function __construct(
        private readonly DomainMemberPointsService $pointsService,
        private readonly DomainMemberGrowthService $growthService,
    ) {}

    public function listen(): array
    {
        return [
            OrderPaidForMember::class,
        ];
    }

    public function process(object $event): void
    {
        if (! $event instanceof OrderPaidForMember) {
            return;
        }

        \Hyperf\Coroutine\co(function () use ($event) {
            // 发放消费返积分
            $balanceEvent = $this->pointsService->grantPurchasePoints(
                $event->memberId,
                $event->payAmountCents,
                $event->orderNo,
            );
            if ($balanceEvent !== null) {
                event($balanceEvent);
            }

            // 增加成长值（实付金额单位为分，直接作为成长值增量）
            $growthEvent = $this->growthService->addGrowthValue(
                $event->memberId,
                $event->payAmountCents,
                'order_payment',
                '消费增加成长值:' . $event->orderNo,
            );
            if ($growthEvent !== null) {
                event($growthEvent);
            }
        });
    }
}
