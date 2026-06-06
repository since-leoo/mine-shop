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
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * 注册送积分监听器：监听会员注册事件，赠送注册积分.
 */
final class RegisterPointsListener implements ListenerInterface
{
    public function __construct(
        private readonly DomainMemberPointsService $pointsService,
        private readonly EventDispatcherInterface $dispatcher,
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

        $handler = fn () => $this->grantRewards($event);
        if (class_exists(\Swoole\Coroutine::class)) {
            \Hyperf\Coroutine\co($handler);
            return;
        }

        $handler();
    }

    private function grantRewards(MemberRegistered $event): void
    {
        $registerEvent = $this->pointsService->grantRegisterPoints($event->memberId);
        if ($registerEvent !== null) {
            $this->dispatcher->dispatch($registerEvent);
        }

        if ($event->referrerId === null) {
            return;
        }

        $inviteEvent = $this->pointsService->grantInvitePoints($event->referrerId, $event->memberId);
        if ($inviteEvent !== null) {
            $this->dispatcher->dispatch($inviteEvent);
        }
    }
}
