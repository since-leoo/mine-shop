<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Infrastructure\Crontab;

use App\Domain\Member\Service\DomainMemberPointsExpireService;
use App\Infrastructure\Crontab\PointsExpireCrontab;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @internal
 * @coversNothing
 */
final class PointsExpireCrontabTest extends TestCase
{
    public function testExecuteRunsPointsExpireService(): void
    {
        $service = $this->createMock(DomainMemberPointsExpireService::class);
        $service
            ->expects(self::once())
            ->method('expireDuePoints')
            ->willReturn(['members' => 2, 'points' => 300, 'transactions' => 3]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('info')
            ->with('[PointsExpire] expired member points', ['members' => 2, 'points' => 300, 'transactions' => 3]);

        (new PointsExpireCrontab($service, $logger))->execute();
    }

    public function testExecuteLogsAndRethrowsFailure(): void
    {
        $exception = new \RuntimeException('boom');
        $service = $this->createMock(DomainMemberPointsExpireService::class);
        $service
            ->expects(self::once())
            ->method('expireDuePoints')
            ->willThrowException($exception);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('error')
            ->with('[PointsExpire] failed to expire member points', ['error' => 'boom']);

        $this->expectExceptionObject($exception);
        (new PointsExpireCrontab($service, $logger))->execute();
    }
}
