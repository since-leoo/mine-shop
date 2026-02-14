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

use App\Domain\Member\Event\MemberGrowthChanged;
use App\Domain\Member\Service\DomainMemberGrowthService;
use Hyperf\Event\Contract\ListenerInterface;

/**
 * 等级升降级监听器：监听成长值变动事件，重新计算会员等级.
 */
final class LevelUpgradeListener implements ListenerInterface
{
    public function __construct(
        private readonly DomainMemberGrowthService $growthService,
    ) {}

    public function listen(): array
    {
        return [
            MemberGrowthChanged::class,
        ];
    }

    public function process(object $event): void
    {
        if (! $event instanceof MemberGrowthChanged) {
            return;
        }

        \Hyperf\Coroutine\co(function () use ($event) {
            $this->growthService->recalculateLevel($event->memberId);
        });
    }
}
