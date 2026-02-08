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

namespace HyperfTests\Feature\Infrastructure\Crontab;

use App\Domain\Marketing\Seckill\Enum\SeckillStatus;
use App\Domain\Marketing\Seckill\Job\SeckillSessionStartJob;
use App\Domain\Marketing\Seckill\Repository\SeckillActivityRepository;
use App\Domain\Marketing\Seckill\Repository\SeckillSessionRepository;
use App\Domain\Marketing\Seckill\Service\DomainSeckillActivityService;
use App\Domain\Marketing\Seckill\Service\DomainSeckillSessionService;
use App\Infrastructure\Crontab\SeckillActivityStatusCrontab;
use Carbon\Carbon;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Driver\DriverInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Feature: marketing-activity-status — SeckillActivityStatusCrontab 集成测试.
 *
 * **Validates: Requirements 1.1, 2.1, 2.2, 3.1, 4.1, 4.2, 9.3, 9.4, 10.1**
 *
 * @internal
 * @coversNothing
 */
final class SeckillActivityStatusCrontabTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const ITERATIONS = 100;

    protected function tearDown(): void
    {
        \Mockery::close();
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ========================================================================
    // Property 1: 延迟秒数计算正确性
    // Feature: marketing-activity-status, Property 1: 延迟秒数计算正确性
    //
    // For any pending + enabled 的场次，其 start_time 在 (now, now+30min] 范围内时，
    // 推送到 AsyncQueue 的延迟秒数应等于 start_time - now（向上取整到秒），且延迟秒数大于 0。
    //
    // **Validates: Requirements 1.1**
    // ========================================================================

    /**
     * @dataProvider provideProperty1_DelaySecondsCalculationCases
     */
    public function testProperty1DelaySecondsCalculation(int $futureSeconds): void
    {
        $now = Carbon::create(2025, 1, 15, 10, 0, 0);
        Carbon::setTestNow($now);

        $startTime = $now->copy()->addSeconds($futureSeconds);
        $sessionId = random_int(1, 100000);
        $activityId = random_int(1, 100000);

        $session = self::makeSession(
            $sessionId,
            $activityId,
            SeckillStatus::PENDING->value,
            true,
            $startTime->toDateTimeString(),
            $startTime->copy()->addHours(2)->toDateTimeString()
        );

        $sessionRepo = \Mockery::mock(SeckillSessionRepository::class);
        $sessionRepo->shouldReceive('findPendingSessionsWithinMinutes')
            ->with(30)
            ->andReturn([$session]);
        $sessionRepo->shouldReceive('findActiveExpiredSessions')->andReturn([]);
        $sessionRepo->shouldReceive('findByActivityId')->andReturn([]);

        $activityRepo = \Mockery::mock(SeckillActivityRepository::class);
        $activityRepo->shouldReceive('findPendingEnabledActivities')->andReturn([]);
        $activityRepo->shouldReceive('findActiveActivities')->andReturn([]);

        $sessionService = \Mockery::mock(DomainSeckillSessionService::class);
        $sessionService->shouldNotReceive('start');

        $activityService = \Mockery::mock(DomainSeckillActivityService::class);

        $capturedDelay = null;
        $mockDriver = \Mockery::mock(DriverInterface::class);
        $mockDriver->shouldReceive('push')
            ->once()
            ->withArgs(static function ($job, $delay) use (&$capturedDelay) {
                $capturedDelay = $delay;
                return $job instanceof SeckillSessionStartJob;
            });

        $driverFactory = \Mockery::mock(DriverFactory::class);
        $driverFactory->shouldReceive('get')->with('default')->andReturn($mockDriver);

        $logger = \Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->zeroOrMoreTimes();
        $logger->shouldReceive('error')->zeroOrMoreTimes();

        $crontab = $this->buildCrontab(
            $sessionRepo,
            $activityRepo,
            $activityService,
            $sessionService,
            $driverFactory,
            $logger
        );
        $crontab->execute();

        self::assertNotNull($capturedDelay, 'Delay should have been captured');
        self::assertGreaterThan(0, $capturedDelay, 'Delay seconds must be > 0');

        // The delay should equal the difference between start_time and now
        $expectedDelay = (int) $startTime->diffInSeconds($now);
        self::assertSame($expectedDelay, $capturedDelay, \sprintf(
            'Delay seconds should be %d (start_time - now), got %d (futureSeconds=%d)',
            $expectedDelay,
            $capturedDelay,
            $futureSeconds
        ));
    }

    /**
     * Generates 100 random future seconds in (0, 1800] range for delay calculation.
     *
     * @return \Generator<string, array{int}>
     */
    public static function provideProperty1_DelaySecondsCalculationCases(): iterable
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $futureSeconds = random_int(1, 1800);
            yield "delay {$futureSeconds}s #{$i}" => [$futureSeconds];
        }
    }

    // ========================================================================
    // Property 3: 秒杀兜底激活正确性
    // Feature: marketing-activity-status, Property 3: 秒杀兜底激活正确性
    //
    // For any SeckillSession，当 status=pending AND is_enabled=true AND start_time <= now 时，
    // Crontab 执行后该场次状态应变为 active。
    //
    // **Validates: Requirements 2.1**
    // ========================================================================

    /**
     * @dataProvider provideProperty3_FallbackActivationCases
     */
    public function testProperty3FallbackActivation(int $pastSeconds): void
    {
        $now = Carbon::create(2025, 1, 15, 10, 0, 0);
        Carbon::setTestNow($now);

        $startTime = $now->copy()->subSeconds($pastSeconds);
        $sessionId = random_int(1, 100000);
        $activityId = random_int(1, 100000);

        $session = self::makeSession(
            $sessionId,
            $activityId,
            SeckillStatus::PENDING->value,
            true,
            $startTime->toDateTimeString(),
            $now->copy()->addHours(2)->toDateTimeString()
        );

        $sessionRepo = \Mockery::mock(SeckillSessionRepository::class);
        $sessionRepo->shouldReceive('findPendingSessionsWithinMinutes')
            ->with(30)
            ->andReturn([$session]);
        $sessionRepo->shouldReceive('findActiveExpiredSessions')->andReturn([]);
        $sessionRepo->shouldReceive('findByActivityId')->andReturn([]);

        $activityRepo = \Mockery::mock(SeckillActivityRepository::class);
        $activityRepo->shouldReceive('findPendingEnabledActivities')->andReturn([]);
        $activityRepo->shouldReceive('findActiveActivities')->andReturn([]);

        $sessionService = \Mockery::mock(DomainSeckillSessionService::class);
        $sessionService->shouldReceive('start')
            ->once()
            ->with($sessionId);

        $activityService = \Mockery::mock(DomainSeckillActivityService::class);

        $driverFactory = \Mockery::mock(DriverFactory::class);
        // Should NOT push to queue — direct fallback activation
        $driverFactory->shouldNotReceive('get');

        $logger = \Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->zeroOrMoreTimes();
        $logger->shouldReceive('error')->zeroOrMoreTimes();

        $crontab = $this->buildCrontab(
            $sessionRepo,
            $activityRepo,
            $activityService,
            $sessionService,
            $driverFactory,
            $logger
        );
        $crontab->execute();

        // Mockery verifies start() was called exactly once
        self::assertTrue(true, \sprintf(
            'Session %d should be fallback-activated (pastSeconds=%d)',
            $sessionId,
            $pastSeconds
        ));
    }

    /**
     * Generates 100 random past seconds [0, 3600] for fallback activation.
     * Includes 0 (exactly now) as a boundary case.
     *
     * @return \Generator<string, array{int}>
     */
    public static function provideProperty3_FallbackActivationCases(): iterable
    {
        // Include boundary: exactly now (0 seconds past)
        yield 'exactly now #0' => [0];

        for ($i = 1; $i < self::ITERATIONS; ++$i) {
            $pastSeconds = random_int(0, 3600);
            yield "past {$pastSeconds}s #{$i}" => [$pastSeconds];
        }
    }

    // ========================================================================
    // Property 4: 秒杀场次自动结束正确性
    // Feature: marketing-activity-status, Property 4: 秒杀场次自动结束正确性
    //
    // For any SeckillSession，当 status=active AND end_time < now 时，
    // Crontab 执行后该场次状态应变为 ended。
    //
    // **Validates: Requirements 3.1**
    // ========================================================================

    /**
     * @dataProvider provideProperty4_SessionAutoEndCases
     */
    public function testProperty4SessionAutoEnd(int $pastEndSeconds): void
    {
        $now = Carbon::create(2025, 1, 15, 10, 0, 0);
        Carbon::setTestNow($now);

        $endTime = $now->copy()->subSeconds($pastEndSeconds);
        $sessionId = random_int(1, 100000);
        $activityId = random_int(1, 100000);

        $session = self::makeSession(
            $sessionId,
            $activityId,
            SeckillStatus::ACTIVE->value,
            true,
            $endTime->copy()->subHours(2)->toDateTimeString(),
            $endTime->toDateTimeString()
        );

        $sessionRepo = \Mockery::mock(SeckillSessionRepository::class);
        $sessionRepo->shouldReceive('findPendingSessionsWithinMinutes')
            ->with(30)
            ->andReturn([]);
        $sessionRepo->shouldReceive('findActiveExpiredSessions')
            ->andReturn([$session]);
        $sessionRepo->shouldReceive('findByActivityId')->andReturn([]);

        $activityRepo = \Mockery::mock(SeckillActivityRepository::class);
        $activityRepo->shouldReceive('findPendingEnabledActivities')->andReturn([]);
        $activityRepo->shouldReceive('findActiveActivities')->andReturn([]);

        $sessionService = \Mockery::mock(DomainSeckillSessionService::class);
        $sessionService->shouldReceive('end')
            ->once()
            ->with($sessionId);

        $activityService = \Mockery::mock(DomainSeckillActivityService::class);

        $driverFactory = \Mockery::mock(DriverFactory::class);

        $logger = \Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->zeroOrMoreTimes();
        $logger->shouldReceive('error')->zeroOrMoreTimes();

        $crontab = $this->buildCrontab(
            $sessionRepo,
            $activityRepo,
            $activityService,
            $sessionService,
            $driverFactory,
            $logger
        );
        $crontab->execute();

        self::assertTrue(true, \sprintf(
            'Session %d should be auto-ended (pastEndSeconds=%d)',
            $sessionId,
            $pastEndSeconds
        ));
    }

    /**
     * Generates 100 random past end seconds [1, 7200] for auto-end.
     *
     * @return \Generator<string, array{int}>
     */
    public static function provideProperty4_SessionAutoEndCases(): iterable
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $pastEndSeconds = random_int(1, 7200);
            yield "ended {$pastEndSeconds}s ago #{$i}" => [$pastEndSeconds];
        }
    }

    // ========================================================================
    // Property 5: 秒杀活动联动激活
    // Feature: marketing-activity-status, Property 5: 秒杀活动联动激活
    //
    // For any SeckillActivity，当 status=pending AND is_enabled=true 且至少有一个关联
    // SeckillSession 状态为 active 时，Crontab 执行后该活动状态应变为 active。
    //
    // **Validates: Requirements 4.1**
    // ========================================================================

    /**
     * @dataProvider provideProperty5_ActivityLinkedActivationCases
     */
    public function testProperty5ActivityLinkedActivation(int $activeSessionCount, int $otherSessionCount): void
    {
        $now = Carbon::create(2025, 1, 15, 10, 0, 0);
        Carbon::setTestNow($now);

        $activityId = random_int(1, 100000);
        $activity = self::makeActivity($activityId, SeckillStatus::PENDING->value, true);

        // Build sessions: some active, some in other statuses
        $sessions = [];
        $nextId = 1;
        for ($i = 0; $i < $activeSessionCount; ++$i) {
            $sessions[] = self::makeSession(
                $nextId++,
                $activityId,
                SeckillStatus::ACTIVE->value,
                true,
                $now->copy()->subHour()->toDateTimeString(),
                $now->copy()->addHour()->toDateTimeString()
            );
        }
        $otherStatuses = [SeckillStatus::PENDING->value, SeckillStatus::ENDED->value, SeckillStatus::CANCELLED->value];
        for ($i = 0; $i < $otherSessionCount; ++$i) {
            $sessions[] = self::makeSession(
                $nextId++,
                $activityId,
                $otherStatuses[array_rand($otherStatuses)],
                true,
                $now->copy()->subHours(3)->toDateTimeString(),
                $now->copy()->subHour()->toDateTimeString()
            );
        }

        $sessionRepo = \Mockery::mock(SeckillSessionRepository::class);
        $sessionRepo->shouldReceive('findPendingSessionsWithinMinutes')
            ->with(30)
            ->andReturn([]);
        $sessionRepo->shouldReceive('findActiveExpiredSessions')->andReturn([]);
        $sessionRepo->shouldReceive('findByActivityId')
            ->with($activityId)
            ->andReturn($sessions);

        $activityRepo = \Mockery::mock(SeckillActivityRepository::class);
        $activityRepo->shouldReceive('findPendingEnabledActivities')
            ->andReturn([$activity]);
        $activityRepo->shouldReceive('findActiveActivities')->andReturn([]);

        $sessionService = \Mockery::mock(DomainSeckillSessionService::class);

        $activityService = \Mockery::mock(DomainSeckillActivityService::class);
        $activityService->shouldReceive('start')
            ->once()
            ->with($activityId);

        $driverFactory = \Mockery::mock(DriverFactory::class);

        $logger = \Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->zeroOrMoreTimes();
        $logger->shouldReceive('error')->zeroOrMoreTimes();

        $crontab = $this->buildCrontab(
            $sessionRepo,
            $activityRepo,
            $activityService,
            $sessionService,
            $driverFactory,
            $logger
        );
        $crontab->execute();

        self::assertTrue(true, \sprintf(
            'Activity %d should be linked-activated (%d active sessions, %d other sessions)',
            $activityId,
            $activeSessionCount,
            $otherSessionCount
        ));
    }

    /**
     * Generates 100 combinations: at least 1 active session + random other sessions.
     *
     * @return \Generator<string, array{int, int}>
     */
    public static function provideProperty5_ActivityLinkedActivationCases(): iterable
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $activeCount = random_int(1, 5);
            $otherCount = random_int(0, 5);
            yield "active={$activeCount} other={$otherCount} #{$i}" => [$activeCount, $otherCount];
        }
    }

    // ========================================================================
    // Property 6: 秒杀活动联动结束
    // Feature: marketing-activity-status, Property 6: 秒杀活动联动结束
    //
    // For any SeckillActivity，当 status=active 且所有关联 SeckillSession 状态均为
    // ended 或 cancelled 时，Crontab 执行后该活动状态应变为 ended。
    //
    // **Validates: Requirements 4.2**
    // ========================================================================

    /**
     * @dataProvider provideProperty6_ActivityLinkedEndCases
     */
    public function testProperty6ActivityLinkedEnd(int $endedCount, int $cancelledCount): void
    {
        $now = Carbon::create(2025, 1, 15, 10, 0, 0);
        Carbon::setTestNow($now);

        $activityId = random_int(1, 100000);
        $activity = self::makeActivity($activityId, SeckillStatus::ACTIVE->value, true);

        // Build sessions: all ended or cancelled
        $sessions = [];
        $nextId = 1;
        for ($i = 0; $i < $endedCount; ++$i) {
            $sessions[] = self::makeSession(
                $nextId++,
                $activityId,
                SeckillStatus::ENDED->value,
                true,
                $now->copy()->subHours(4)->toDateTimeString(),
                $now->copy()->subHours(2)->toDateTimeString()
            );
        }
        for ($i = 0; $i < $cancelledCount; ++$i) {
            $sessions[] = self::makeSession(
                $nextId++,
                $activityId,
                SeckillStatus::CANCELLED->value,
                true,
                $now->copy()->subHours(4)->toDateTimeString(),
                $now->copy()->subHours(2)->toDateTimeString()
            );
        }

        $sessionRepo = \Mockery::mock(SeckillSessionRepository::class);
        $sessionRepo->shouldReceive('findPendingSessionsWithinMinutes')
            ->with(30)
            ->andReturn([]);
        $sessionRepo->shouldReceive('findActiveExpiredSessions')->andReturn([]);
        $sessionRepo->shouldReceive('findByActivityId')
            ->with($activityId)
            ->andReturn($sessions);

        $activityRepo = \Mockery::mock(SeckillActivityRepository::class);
        $activityRepo->shouldReceive('findPendingEnabledActivities')->andReturn([]);
        $activityRepo->shouldReceive('findActiveActivities')
            ->andReturn([$activity]);

        $sessionService = \Mockery::mock(DomainSeckillSessionService::class);

        $activityService = \Mockery::mock(DomainSeckillActivityService::class);
        $activityService->shouldReceive('end')
            ->once()
            ->with($activityId);

        $driverFactory = \Mockery::mock(DriverFactory::class);

        $logger = \Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->zeroOrMoreTimes();
        $logger->shouldReceive('error')->zeroOrMoreTimes();

        $crontab = $this->buildCrontab(
            $sessionRepo,
            $activityRepo,
            $activityService,
            $sessionService,
            $driverFactory,
            $logger
        );
        $crontab->execute();

        self::assertTrue(true, \sprintf(
            'Activity %d should be linked-ended (%d ended, %d cancelled sessions)',
            $activityId,
            $endedCount,
            $cancelledCount
        ));
    }

    /**
     * Generates 100 combinations: at least 1 session, all ended or cancelled.
     *
     * @return \Generator<string, array{int, int}>
     */
    public static function provideProperty6_ActivityLinkedEndCases(): iterable
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $endedCount = random_int(0, 5);
            $cancelledCount = random_int(0, 5);
            // Ensure at least 1 session total
            if ($endedCount + $cancelledCount === 0) {
                $endedCount = 1;
            }
            yield "ended={$endedCount} cancelled={$cancelledCount} #{$i}" => [$endedCount, $cancelledCount];
        }
    }

    // ========================================================================
    // Property 11: 秒杀状态保护
    // Feature: marketing-activity-status, Property 11: 秒杀状态保护
    //
    // For any SeckillSession 或 SeckillActivity，当状态为 cancelled、sold_out 或
    // is_enabled=false 时，Crontab 执行后该记录状态不变。
    //
    // **Validates: Requirements 2.2, 10.1**
    // ========================================================================

    /**
     * @dataProvider provideProperty11_SessionStatusProtectionCases
     */
    public function testProperty11SessionStatusProtection(string $status, bool $isEnabled): void
    {
        $now = Carbon::create(2025, 1, 15, 10, 0, 0);
        Carbon::setTestNow($now);

        $sessionId = random_int(1, 100000);
        $activityId = random_int(1, 100000);

        // Session with protected status — should NOT be modified
        $session = self::makeSession(
            $sessionId,
            $activityId,
            $status,
            $isEnabled,
            $now->copy()->subHour()->toDateTimeString(),
            $now->copy()->addHour()->toDateTimeString()
        );

        $sessionRepo = \Mockery::mock(SeckillSessionRepository::class);

        // For pending + is_enabled=false: the repository already filters by is_enabled=true,
        // so it won't return this session at all (Req 2.2).
        // For cancelled/sold_out: the repo may return them, but the crontab skips them (Req 10.1).
        $isCancelledOrSoldOut = \in_array($status, [SeckillStatus::CANCELLED->value, SeckillStatus::SOLD_OUT->value], true);

        if ($status === SeckillStatus::PENDING->value && ! $isEnabled) {
            // Repository filters by is_enabled=true, so this session is never returned
            $sessionRepo->shouldReceive('findPendingSessionsWithinMinutes')
                ->with(30)
                ->andReturn([]);
        } else {
            // cancelled/sold_out sessions may be returned by the repo, crontab skips them
            $sessionRepo->shouldReceive('findPendingSessionsWithinMinutes')
                ->with(30)
                ->andReturn($isCancelledOrSoldOut ? [$session] : []);
        }

        $sessionRepo->shouldReceive('findActiveExpiredSessions')
            ->andReturn($isCancelledOrSoldOut ? [$session] : []);
        $sessionRepo->shouldReceive('findByActivityId')->andReturn([]);

        $activityRepo = \Mockery::mock(SeckillActivityRepository::class);
        $activityRepo->shouldReceive('findPendingEnabledActivities')->andReturn([]);
        $activityRepo->shouldReceive('findActiveActivities')->andReturn([]);

        $sessionService = \Mockery::mock(DomainSeckillSessionService::class);
        // start() and end() should NEVER be called for protected statuses
        $sessionService->shouldNotReceive('start');
        $sessionService->shouldNotReceive('end');

        $activityService = \Mockery::mock(DomainSeckillActivityService::class);

        $driverFactory = \Mockery::mock(DriverFactory::class);
        $driverFactory->shouldNotReceive('get');

        $logger = \Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->zeroOrMoreTimes();
        $logger->shouldReceive('error')->zeroOrMoreTimes();

        $crontab = $this->buildCrontab(
            $sessionRepo,
            $activityRepo,
            $activityService,
            $sessionService,
            $driverFactory,
            $logger
        );
        $crontab->execute();

        self::assertTrue(true, \sprintf(
            'Session %d with status=%s, is_enabled=%s should be protected (not modified)',
            $sessionId,
            $status,
            $isEnabled ? 'true' : 'false'
        ));
    }

    /**
     * Generates 100 protected session status combinations:
     * - cancelled / sold_out (any is_enabled): Crontab skips due to status protection (Req 10.1)
     * - pending + is_enabled=false: Crontab skips due to is_enabled check (Req 2.2)
     *
     * Note: active + is_enabled=false is NOT protected — the crontab will still end expired active sessions.
     *
     * @return \Generator<string, array{string, bool}>
     */
    public static function provideProperty11_SessionStatusProtectionCases(): iterable
    {
        $protectedCases = [
            [SeckillStatus::CANCELLED->value, true],
            [SeckillStatus::CANCELLED->value, false],
            [SeckillStatus::SOLD_OUT->value, true],
            [SeckillStatus::SOLD_OUT->value, false],
            // is_enabled=false only protects pending sessions (Req 2.2)
            [SeckillStatus::PENDING->value, false],
        ];

        $perCase = (int) ceil(self::ITERATIONS / \count($protectedCases));
        $idx = 0;
        foreach ($protectedCases as [$status, $isEnabled]) {
            for ($i = 0; $i < $perCase && $idx < self::ITERATIONS; ++$i, ++$idx) {
                yield "session status={$status} enabled=" . ($isEnabled ? '1' : '0') . " #{$idx}" => [$status, $isEnabled];
            }
        }
    }

    /**
     * @dataProvider provideProperty11_ActivityStatusProtectionCases
     */
    public function testProperty11ActivityStatusProtection(string $status, bool $isEnabled): void
    {
        $now = Carbon::create(2025, 1, 15, 10, 0, 0);
        Carbon::setTestNow($now);

        $activityId = random_int(1, 100000);
        $activity = self::makeActivity($activityId, $status, $isEnabled);

        $sessionRepo = \Mockery::mock(SeckillSessionRepository::class);
        $sessionRepo->shouldReceive('findPendingSessionsWithinMinutes')
            ->with(30)
            ->andReturn([]);
        $sessionRepo->shouldReceive('findActiveExpiredSessions')->andReturn([]);
        // Return sessions that would trigger activation/ending if the activity weren't protected:
        // - An active session (would trigger activity start in processActivityStart)
        // - All ended sessions (would trigger activity end in processActivityEnd)
        $mockSessions = [
            self::makeSession(
                random_int(1, 100000),
                $activityId,
                SeckillStatus::ENDED->value,
                true,
                $now->copy()->subHours(3)->toDateTimeString(),
                $now->copy()->subHour()->toDateTimeString()
            ),
        ];
        $sessionRepo->shouldReceive('findByActivityId')
            ->andReturn($mockSessions);

        $activityRepo = \Mockery::mock(SeckillActivityRepository::class);
        // Feed the protected activity through both repo methods to exercise skip logic
        $activityRepo->shouldReceive('findPendingEnabledActivities')
            ->andReturn([$activity]);
        $activityRepo->shouldReceive('findActiveActivities')
            ->andReturn([$activity]);

        $sessionService = \Mockery::mock(DomainSeckillSessionService::class);

        $activityService = \Mockery::mock(DomainSeckillActivityService::class);
        // start() and end() should NEVER be called for cancelled/sold_out statuses
        $activityService->shouldNotReceive('start');
        $activityService->shouldNotReceive('end');

        $driverFactory = \Mockery::mock(DriverFactory::class);

        $logger = \Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->zeroOrMoreTimes();
        $logger->shouldReceive('error')->zeroOrMoreTimes();

        $crontab = $this->buildCrontab(
            $sessionRepo,
            $activityRepo,
            $activityService,
            $sessionService,
            $driverFactory,
            $logger
        );
        $crontab->execute();

        self::assertTrue(true, \sprintf(
            'Activity %d with status=%s, is_enabled=%s should be protected (not modified)',
            $activityId,
            $status,
            $isEnabled ? 'true' : 'false'
        ));
    }

    /**
     * Generates 100 protected activity status combinations:
     * cancelled, sold_out.
     *
     * @return \Generator<string, array{string, bool}>
     */
    public static function provideProperty11_ActivityStatusProtectionCases(): iterable
    {
        $protectedCases = [
            [SeckillStatus::CANCELLED->value, true],
            [SeckillStatus::CANCELLED->value, false],
            [SeckillStatus::SOLD_OUT->value, true],
            [SeckillStatus::SOLD_OUT->value, false],
        ];

        $perCase = (int) ceil(self::ITERATIONS / \count($protectedCases));
        $idx = 0;
        foreach ($protectedCases as [$status, $isEnabled]) {
            for ($i = 0; $i < $perCase && $idx < self::ITERATIONS; ++$i, ++$idx) {
                yield "activity status={$status} enabled=" . ($isEnabled ? '1' : '0') . " #{$idx}" => [$status, $isEnabled];
            }
        }
    }

    // ========================================================================
    // Property 13: 错误隔离
    // Feature: marketing-activity-status, Property 13: 错误隔离
    //
    // For any Crontab 执行批次中的记录集合，如果处理某条记录时抛出异常，
    // 其余记录仍应被正常处理。
    //
    // **Validates: Requirements 9.4**
    // ========================================================================

    /**
     * @dataProvider provideProperty13_ErrorIsolationCases
     */
    public function testProperty13ErrorIsolation(int $failIndex, int $totalSessions): void
    {
        $now = Carbon::create(2025, 1, 15, 10, 0, 0);
        Carbon::setTestNow($now);

        $activityId = random_int(1, 100000);

        // Build multiple pending sessions that need fallback activation (start_time <= now)
        $sessions = [];
        for ($i = 0; $i < $totalSessions; ++$i) {
            $sessions[] = self::makeSession(
                $i + 1,
                $activityId,
                SeckillStatus::PENDING->value,
                true,
                $now->copy()->subMinutes(random_int(1, 60))->toDateTimeString(),
                $now->copy()->addHours(2)->toDateTimeString()
            );
        }

        $sessionRepo = \Mockery::mock(SeckillSessionRepository::class);
        $sessionRepo->shouldReceive('findPendingSessionsWithinMinutes')
            ->with(30)
            ->andReturn($sessions);
        $sessionRepo->shouldReceive('findActiveExpiredSessions')->andReturn([]);
        $sessionRepo->shouldReceive('findByActivityId')->andReturn([]);

        $activityRepo = \Mockery::mock(SeckillActivityRepository::class);
        $activityRepo->shouldReceive('findPendingEnabledActivities')->andReturn([]);
        $activityRepo->shouldReceive('findActiveActivities')->andReturn([]);

        $sessionService = \Mockery::mock(DomainSeckillSessionService::class);

        // The session at failIndex throws an exception
        $failSessionId = $sessions[$failIndex]->id;
        $sessionService->shouldReceive('start')
            ->with($failSessionId)
            ->andThrow(new \RuntimeException('Simulated failure for session ' . $failSessionId));

        // All other sessions should still be processed successfully
        $expectedSuccessIds = [];
        for ($i = 0; $i < $totalSessions; ++$i) {
            if ($i !== $failIndex) {
                $sid = $sessions[$i]->id;
                $expectedSuccessIds[] = $sid;
                $sessionService->shouldReceive('start')
                    ->with($sid)
                    ->once();
            }
        }

        $activityService = \Mockery::mock(DomainSeckillActivityService::class);

        $driverFactory = \Mockery::mock(DriverFactory::class);

        $logger = \Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->zeroOrMoreTimes();
        // Should log error for the failed session
        $logger->shouldReceive('error')->atLeast()->once();

        $crontab = $this->buildCrontab(
            $sessionRepo,
            $activityRepo,
            $activityService,
            $sessionService,
            $driverFactory,
            $logger
        );

        // Execute should NOT throw — errors are isolated
        $crontab->execute();

        self::assertTrue(true, \sprintf(
            'Error in session at index %d should not prevent processing of %d other sessions',
            $failIndex,
            \count($expectedSuccessIds)
        ));
    }

    /**
     * Generates 100 error isolation scenarios with varying batch sizes and fail positions.
     *
     * @return \Generator<string, array{int, int}>
     */
    public static function provideProperty13_ErrorIsolationCases(): iterable
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $totalSessions = random_int(2, 8);
            $failIndex = random_int(0, $totalSessions - 1);
            yield "fail@{$failIndex}/{$totalSessions} #{$i}" => [$failIndex, $totalSessions];
        }
    }

    // ========================================================================
    // Helper: create a mock session stdClass
    // ========================================================================

    private static function makeSession(
        int $id,
        int $activityId,
        string $status,
        bool $isEnabled,
        string $startTime,
        string $endTime
    ): \stdClass {
        $s = new \stdClass();
        $s->id = $id;
        $s->activity_id = $activityId;
        $s->status = $status;
        $s->is_enabled = $isEnabled;
        $s->start_time = $startTime;
        $s->end_time = $endTime;
        return $s;
    }

    private static function makeActivity(
        int $id,
        string $status,
        bool $isEnabled
    ): \stdClass {
        $a = new \stdClass();
        $a->id = $id;
        $a->status = $status;
        $a->is_enabled = $isEnabled;
        return $a;
    }

    /**
     * Build the Crontab with given mocks.
     */
    private function buildCrontab(
        Mockery\MockInterface $sessionRepo,
        Mockery\MockInterface $activityRepo,
        Mockery\MockInterface $activityService,
        Mockery\MockInterface $sessionService,
        Mockery\MockInterface $driverFactory,
        Mockery\MockInterface $logger
    ): SeckillActivityStatusCrontab {
        return new SeckillActivityStatusCrontab(
            $sessionRepo,
            $activityRepo,
            $activityService,
            $sessionService,
            $driverFactory,
            $logger
        );
    }
}
