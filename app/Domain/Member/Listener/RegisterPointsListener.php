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

use App\Domain\Member\Event\MemberRegistered;
use App\Domain\Member\Service\DomainMemberPointsService;
use Hyperf\Event\Contract\ListenerInterface;

/**
 * 注册送积分监听器：监听会员注册事件，赠送注册积分.
 */
final class RegisterPointsListener implements ListenerInterface
{
    public function __construct(
        private readonly DomainMemberPointsService $pointsService,
    ) {}

    public function listen(): array
    {
        return [
            MemberRegistered::class,
        ];
    }

    public function process(object $event): void
    {
        if (! $event instanceof MemberRegistered) {
            return;
        }

        \Hyperf\Coroutine\co(function () use ($event) {
            $this->pointsService->grantRegisterPoints($event->memberId);
        });
    }
}
