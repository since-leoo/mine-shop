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

use App\Domain\Marketing\GroupBuy\Enum\GroupBuyStatus;
use App\Domain\Marketing\GroupBuy\Job\GroupBuyStartJob;
use App\Domain\Marketing\GroupBuy\Repository\GroupBuyRepository;
use App\Domain\Marketing\GroupBuy\Service\DomainGroupBuyService;
use App\Infrastructure\Crontab\GroupBuyActivityStatusCrontab;
use Carbon\Carbon;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Driver\DriverInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Feature: marketing-activity-status — GroupBuyActivityStatusCrontab 集成测试.
 *
 * **Validates: Requirements 5.1, 6.1, 6.2, 7.1, 9.3, 9.4, 10.2**
 *
 * @internal
 * @coversNothing
 */
final class GroupBuyActivityStatusCrontabTest extends TestCase
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
    // For any pending + enabled 的活动，其 start_time 在 (now, now+30min] 范围内时，
    // 推送到 AsyncQueue 的延迟秒数应等于 start_time - now（向上取整到秒），且延迟秒数大于 0。
    //
    // **Validates: Requirements 5.1**
    // ========================================================================

    /**
     * @dataProvider provideProperty1_DelaySecondsCalculationCases
     */
    public function testProperty1DelaySecondsCalculation(int $futureSeconds): void
    {
        $now = Carbon::create(2025, 1, 15, 10, 0, 0);
        Carbon::setTestNow($now);

        $startTime = $now->copy()->addSeconds($futureSeconds);
        $activityId = random_int(1, 100000);

        $activity = self::makeActivity(
            $activityId,
            GroupBuyStatus::PENDING->value,
            true,
            $startTime->toDateTimeString(),
            $startTime->copy()->addHours(2)->toDateTimeString()
        );

        $repository = \Mockery::mock(GroupBuyRepository::class);
        $repository->shouldReceive('findPendingActivitiesWithinMinutes')
            ->with(30)
            ->andReturn([$activity]);
        $repository->shouldReceive('findActiveExpiredActivities')->andReturn([]);

        $groupBuyService = \Mockery::mock(DomainGroupBuyService::class);
        $groupBuyService->shouldNotReceive('start');

        $capturedDelay = null;
        $mockDriver = \Mockery::mock(DriverInterface::class);
        $mockDriver->shouldReceive('push')
            ->once()
            ->withArgs(static function ($job, $delay) use (&$capturedDelay) {
                $capturedDelay = $delay;
                return $job instanceof GroupBuyStartJob;
            });

        $driverFactory = \Mockery::mock(DriverFactory::class);
        $driverFactory->shouldReceive('get')->with('default')->andReturn($mockDriver);

        $logger = \Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->zeroOrMoreTimes();
        $logger->shouldReceive('error')->zeroOrMoreTimes();

        $crontab = $this->buildCrontab($repository, $groupBuyService, $driverFactory, $logger);
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
    // Property 7: 拼团兜底激活正确性
    // Feature: marketing-activity-status, Property 7: 拼团兜底激活正确性
    //
    // For any GroupBuyActivity，当 status=pending AND is_enabled=true AND start_time <= now 时，
    // Crontab 执行后该活动状态应变为 active。
    //
    // **Validates: Requirements 6.1**
    // ========================================================================

    /**
     * @dataProvider provideProperty7_FallbackActivationCases
     */
    public function testProperty7FallbackActivation(int $pastSeconds): void
    {
        $now = Carbon::create(2025, 1, 15, 10, 0, 0);
        Carbon::setTestNow($now);

        $startTime = $now->copy()->subSeconds($pastSeconds);
        $activityId = random_int(1, 100000);

        $activity = self::makeActivity(
            $activityId,
            GroupBuyStatus::PENDING->value,
            true,
            $startTime->toDateTimeString(),
            $now->copy()->addHours(2)->toDateTimeString()
        );

        $repository = \Mockery::mock(GroupBuyRepository::class);
        $repository->shouldReceive('findPendingActivitiesWithinMinutes')
            ->with(30)
            ->andReturn([$activity]);
        $repository->shouldReceive('findActiveExpiredActivities')->andReturn([]);

        $groupBuyService = \Mockery::mock(DomainGroupBuyService::class);
        $groupBuyService->shouldReceive('start')
            ->once()
            ->with($activityId);

        $driverFactory = \Mockery::mock(DriverFactory::class);
        // Should NOT push to queue — direct fallback activation
        $driverFactory->shouldNotReceive('get');

        $logger = \Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->zeroOrMoreTimes();
        $logger->shouldReceive('error')->zeroOrMoreTimes();

        $crontab = $this->buildCrontab($repository, $groupBuyService, $driverFactory, $logger);
        $crontab->execute();

        // Mockery verifies start() was called exactly once
        self::assertTrue(true, \sprintf(
            'Activity %d should be fallback-activated (pastSeconds=%d)',
            $activityId,
            $pastSeconds
        ));
    }

    /**
     * Generates 100 random past seconds [0, 3600] for fallback activation.
     * Includes 0 (exactly now) as a boundary case.
     *
     * @return \Generator<string, array{int}>
     */
    public static function provideProperty7_FallbackActivationCases(): iterable
    {
        // Include boundary: exactly now (0 seconds past)
        yield 'exactly now #0' => [0];

        for ($i = 1; $i < self::ITERATIONS; ++$i) {
            $pastSeconds = random_int(0, 3600);
            yield "past {$pastSeconds}s #{$i}" => [$pastSeconds];
        }
    }

    // ========================================================================
    // Property 8: 拼团自动结束正确性
    // Feature: marketing-activity-status, Property 8: 拼团自动结束正确性
    //
    // For any GroupBuyActivity，当 status=active AND end_time < now 时，
    // Crontab 执行后该活动状态应变为 ended。
    //
    // **Validates: Requirements 7.1**
    // ========================================================================

    /**
     * @dataProvider provideProperty8_ActivityAutoEndCases
     */
    public function testProperty8ActivityAutoEnd(int $pastEndSeconds): void
    {
        $now = Carbon::create(2025, 1, 15, 10, 0, 0);
        Carbon::setTestNow($now);

        $endTime = $now->copy()->subSeconds($pastEndSeconds);
        $activityId = random_int(1, 100000);

        $activity = self::makeActivity(
            $activityId,
            GroupBuyStatus::ACTIVE->value,
            true,
            $endTime->copy()->subHours(2)->toDateTimeString(),
            $endTime->toDateTimeString()
        );

        $repository = \Mockery::mock(GroupBuyRepository::class);
        $repository->shouldReceive('findPendingActivitiesWithinMinutes')
            ->with(30)
            ->andReturn([]);
        $repository->shouldReceive('findActiveExpiredActivities')
            ->andReturn([$activity]);

        $groupBuyService = \Mockery::mock(DomainGroupBuyService::class);
        $groupBuyService->shouldReceive('end')
            ->once()
            ->with($activityId);

        $driverFactory = \Mockery::mock(DriverFactory::class);

        $logger = \Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->zeroOrMoreTimes();
        $logger->shouldReceive('error')->zeroOrMoreTimes();

        $crontab = $this->buildCrontab($repository, $groupBuyService, $driverFactory, $logger);
        $crontab->execute();

        self::assertTrue(true, \sprintf(
            'Activity %d should be auto-ended (pastEndSeconds=%d)',
            $activityId,
            $pastEndSeconds
        ));
    }

    /**
     * Generates 100 random past end seconds [1, 7200] for auto-end.
     *
     * @return \Generator<string, array{int}>
     */
    public static function provideProperty8_ActivityAutoEndCases(): iterable
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $pastEndSeconds = random_int(1, 7200);
            yield "ended {$pastEndSeconds}s ago #{$i}" => [$pastEndSeconds];
        }
    }

    // ========================================================================
    // Property 12: 拼团状态保护
    // Feature: marketing-activity-status, Property 12: 拼团状态保护
    //
    // For any GroupBuyActivity，当状态为 cancelled、sold_out 或 is_enabled=false 时，
    // Crontab 执行后该记录状态不变。
    //
    // **Validates: Requirements 6.2, 10.2**
    // ========================================================================

    /**
     * @dataProvider provideProperty12_ActivityStatusProtectionCases
     */
    public function testProperty12ActivityStatusProtection(string $status, bool $isEnabled): void
    {
        $now = Carbon::create(2025, 1, 15, 10, 0, 0);
        Carbon::setTestNow($now);

        $activityId = random_int(1, 100000);

        // Activity with protected status — should NOT be modified
        $activity = self::makeActivity(
            $activityId,
            $status,
            $isEnabled,
            $now->copy()->subHour()->toDateTimeString(),
            $now->copy()->addHour()->toDateTimeString()
        );

        $repository = \Mockery::mock(GroupBuyRepository::class);

        // For pending + is_enabled=false: the repository already filters by is_enabled=true,
        // so it won't return this activity at all (Req 6.2).
        // For cancelled/sold_out: the repo may return them, but the crontab skips them (Req 10.2).
        $isCancelledOrSoldOut = \in_array($status, [GroupBuyStatus::CANCELLED->value, GroupBuyStatus::SOLD_OUT->value], true);

        if ($status === GroupBuyStatus::PENDING->value && ! $isEnabled) {
            // Repository filters by is_enabled=true, so this activity is never returned
            $repository->shouldReceive('findPendingActivitiesWithinMinutes')
                ->with(30)
                ->andReturn([]);
        } else {
            // cancelled/sold_out activities may be returned by the repo, crontab skips them
            $repository->shouldReceive('findPendingActivitiesWithinMinutes')
                ->with(30)
                ->andReturn($isCancelledOrSoldOut ? [$activity] : []);
        }

        $repository->shouldReceive('findActiveExpiredActivities')
            ->andReturn($isCancelledOrSoldOut ? [$activity] : []);

        $groupBuyService = \Mockery::mock(DomainGroupBuyService::class);
        // start() and end() should NEVER be called for protected statuses
        $groupBuyService->shouldNotReceive('start');
        $groupBuyService->shouldNotReceive('end');

        $driverFactory = \Mockery::mock(DriverFactory::class);
        $driverFactory->shouldNotReceive('get');

        $logger = \Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->zeroOrMoreTimes();
        $logger->shouldReceive('error')->zeroOrMoreTimes();

        $crontab = $this->buildCrontab($repository, $groupBuyService, $driverFactory, $logger);
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
     * - cancelled / sold_out (any is_enabled): Crontab skips due to status protection (Req 10.2)
     * - pending + is_enabled=false: Crontab skips due to is_enabled check (Req 6.2)
     *
     * @return \Generator<string, array{string, bool}>
     */
    public static function provideProperty12_ActivityStatusProtectionCases(): iterable
    {
        $protectedCases = [
            [GroupBuyStatus::CANCELLED->value, true],
            [GroupBuyStatus::CANCELLED->value, false],
            [GroupBuyStatus::SOLD_OUT->value, true],
            [GroupBuyStatus::SOLD_OUT->value, false],
            // is_enabled=false only protects pending activities (Req 6.2)
            [GroupBuyStatus::PENDING->value, false],
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
    // Helper: create a mock activity stdClass
    // ========================================================================

    private static function makeActivity(
        int $id,
        string $status,
        bool $isEnabled,
        string $startTime,
        string $endTime
    ): \stdClass {
        $a = new \stdClass();
        $a->id = $id;
        $a->status = $status;
        $a->is_enabled = $isEnabled;
        $a->start_time = $startTime;
        $a->end_time = $endTime;
        return $a;
    }

    /**
     * Build the Crontab with given mocks.
     */
    private function buildCrontab(
        Mockery\MockInterface $repository,
        Mockery\MockInterface $groupBuyService,
        Mockery\MockInterface $driverFactory,
        Mockery\MockInterface $logger
    ): GroupBuyActivityStatusCrontab {
        return new GroupBuyActivityStatusCrontab(
            $repository,
            $groupBuyService,
            $driverFactory,
            $logger
        );
    }
}
