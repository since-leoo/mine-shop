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

namespace HyperfTests\Unit\Distribution;

use App\Domain\Auth\Enum\Status;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Model\Permission\User;
use App\Interface\Common\CurrentUser;
use App\Interface\Common\ResultCode;
use PHPUnit\Framework\TestCase;
use Plugin\Distribution\Interface\Admin\Middleware\DistributionAdminMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 * @coversNothing
 */
final class DistributionAdminMiddlewareTest extends TestCase
{
    public function testActiveSuperAdministratorCanManageWithoutAnyMenuPermissions(): void
    {
        $user = $this->user(true, Status::Normal);
        $user->expects(self::never())->method('getPermissions');
        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->method('user')->willReturn($user);
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->with($request)->willReturn($response);
        self::assertSame($response, (new DistributionAdminMiddleware($currentUser))->process($request, $handler));
    }

    public function testOrdinaryAdministratorCannotReadOrWriteDistributionData(): void
    {
        $this->assertDenied($this->user(false, Status::Normal), ResultCode::FORBIDDEN);
    }

    public function testDisabledSuperAdministratorCannotApproveOrPayWithdrawals(): void
    {
        $this->assertDenied($this->user(true, Status::DISABLE), ResultCode::DISABLED);
    }

    public function testMissingAdministratorCannotEnterTheController(): void
    {
        $this->assertDenied(null, ResultCode::FORBIDDEN);
    }

    private function user(bool $superAdministrator, Status $status): User
    {
        $user = $this->getMockBuilder(User::class)->disableOriginalConstructor()->onlyMethods(['isSuperAdmin', 'getPermissions'])->getMock();
        $user->method('isSuperAdmin')->willReturn($superAdministrator);
        $user->setAttribute('status', $status);
        return $user;
    }

    private function assertDenied(?User $user, ResultCode $code): void
    {
        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->method('user')->willReturn($user);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');
        try {
            (new DistributionAdminMiddleware($currentUser))->process($this->createMock(ServerRequestInterface::class), $handler);
            self::fail('Unauthorized administrators must never reach financial endpoints');
        } catch (BusinessException $exception) {
            self::assertSame($code, $exception->getResponse()->code);
        }
    }
}
