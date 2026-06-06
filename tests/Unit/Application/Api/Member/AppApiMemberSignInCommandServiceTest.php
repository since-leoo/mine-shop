<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Application\Api\Member;

use App\Application\Api\Member\AppApiMemberSignInCommandService;
use App\Domain\Member\Event\MemberBalanceAdjusted;
use App\Domain\Member\Service\DomainMemberPointsService;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 * @coversNothing
 */
final class AppApiMemberSignInCommandServiceTest extends TestCase
{
    public function testSignInReturnsGrantedResultWhenPointsWereGranted(): void
    {
        $pointsService = $this->createMock(DomainMemberPointsService::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $event = new MemberBalanceAdjusted(
            memberId: 1,
            walletId: 2,
            walletType: 'points',
            changeAmount: 5,
            beforeBalance: 10,
            afterBalance: 15,
            source: 'sign_in',
            remark: 'sign in',
            relatedType: 'sign_in_date',
            relatedId: 20260606,
        );

        $pointsService->expects(self::once())
            ->method('grantSignInPoints')
            ->with(1)
            ->willReturn($event);

        $dispatcher->expects(self::once())->method('dispatch')->with($event);

        $service = new AppApiMemberSignInCommandService($pointsService, $dispatcher);
        $result = $service->signIn(1);

        self::assertSame(['signed' => true, 'points' => 5, 'already_signed' => false], $result);
    }

    public function testSignInReturnsIdempotentResultWhenNothingWasGranted(): void
    {
        $pointsService = $this->createMock(DomainMemberPointsService::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $pointsService->expects(self::once())
            ->method('grantSignInPoints')
            ->with(1)
            ->willReturn(null);

        $dispatcher->expects(self::never())->method('dispatch');

        $service = new AppApiMemberSignInCommandService($pointsService, $dispatcher);
        $result = $service->signIn(1);

        self::assertSame(['signed' => true, 'points' => 0, 'already_signed' => true], $result);
    }
}
