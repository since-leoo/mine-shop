<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Member\Listener;

use App\Domain\Member\Event\MemberBalanceAdjusted;
use App\Domain\Member\Event\MemberRegistered;
use App\Domain\Member\Listener\RegisterPointsListener;
use App\Domain\Member\Service\DomainMemberPointsService;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 * @coversNothing
 */
final class RegisterPointsListenerTest extends TestCase
{
    public function testProcessGrantsRegisterAndInviteRewardsWhenReferrerExists(): void
    {
        $pointsService = $this->createMock(DomainMemberPointsService::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $registerEvent = new MemberBalanceAdjusted(20, 1, 'points', 100, 0, 100, 'register');
        $inviteEvent = new MemberBalanceAdjusted(10, 2, 'points', 50, 0, 50, 'invite_reward');

        $pointsService->expects(self::once())
            ->method('grantRegisterPoints')
            ->with(20)
            ->willReturn($registerEvent);

        $pointsService->expects(self::once())
            ->method('grantInvitePoints')
            ->with(10, 20)
            ->willReturn($inviteEvent);

        $dispatched = [];
        $dispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnCallback(static function (object $event) use (&$dispatched): object {
                $dispatched[] = $event;
                return $event;
            });

        $listener = new RegisterPointsListener($pointsService, $dispatcher);
        $listener->process(new MemberRegistered(memberId: 20, source: 'mini_program', referrerId: 10));

        self::assertSame([$registerEvent, $inviteEvent], $dispatched);
    }

    public function testProcessSkipsInviteRewardWhenReferrerIsMissing(): void
    {
        $pointsService = $this->createMock(DomainMemberPointsService::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $pointsService->expects(self::once())
            ->method('grantRegisterPoints')
            ->with(20)
            ->willReturn(null);

        $pointsService->expects(self::never())->method('grantInvitePoints');
        $dispatcher->expects(self::never())->method('dispatch');

        $listener = new RegisterPointsListener($pointsService, $dispatcher);
        $listener->process(new MemberRegistered(memberId: 20, source: 'mini_program', referrerId: null));

        self::assertTrue(true);
    }
}
