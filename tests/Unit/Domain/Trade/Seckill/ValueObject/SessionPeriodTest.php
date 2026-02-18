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

namespace HyperfTests\Unit\Domain\Trade\Seckill\ValueObject;

use App\Domain\Trade\Seckill\ValueObject\SessionPeriod;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class SessionPeriodTest extends TestCase
{
    public function testCreateWithStrings(): void
    {
        $period = new SessionPeriod('2026-03-01 10:00:00', '2026-03-01 12:00:00');
        self::assertInstanceOf(Carbon::class, $period->getStartTime());
        self::assertInstanceOf(Carbon::class, $period->getEndTime());
    }

    public function testCreateWithCarbon(): void
    {
        $start = Carbon::parse('2026-03-01 10:00:00');
        $end = Carbon::parse('2026-03-01 12:00:00');
        $period = new SessionPeriod($start, $end);
        self::assertTrue($period->getStartTime()->eq($start));
        self::assertTrue($period->getEndTime()->eq($end));
    }

    public function testStartMustBeBeforeEnd(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new SessionPeriod('2026-03-01 12:00:00', '2026-03-01 10:00:00');
    }

    public function testEqualTimesThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new SessionPeriod('2026-03-01 10:00:00', '2026-03-01 10:00:00');
    }

    public function testGetDurationInHours(): void
    {
        $period = new SessionPeriod('2026-03-01 10:00:00', '2026-03-01 12:00:00');
        self::assertSame(2.0, $period->getDurationInHours());
    }

    public function testGetDurationInMinutes(): void
    {
        $period = new SessionPeriod('2026-03-01 10:00:00', '2026-03-01 10:30:00');
        self::assertSame(30, $period->getDurationInMinutes());
    }

    public function testIsActive(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-01 11:00:00'));
        $period = new SessionPeriod('2026-03-01 10:00:00', '2026-03-01 12:00:00');
        self::assertTrue($period->isActive());
        Carbon::setTestNow();
    }

    public function testIsPending(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-01 09:00:00'));
        $period = new SessionPeriod('2026-03-01 10:00:00', '2026-03-01 12:00:00');
        self::assertTrue($period->isPending());
        Carbon::setTestNow();
    }

    public function testIsEnded(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-01 13:00:00'));
        $period = new SessionPeriod('2026-03-01 10:00:00', '2026-03-01 12:00:00');
        self::assertTrue($period->isEnded());
        Carbon::setTestNow();
    }

    public function testOverlaps(): void
    {
        $p1 = new SessionPeriod('2026-03-01 10:00:00', '2026-03-01 12:00:00');
        $p2 = new SessionPeriod('2026-03-01 11:00:00', '2026-03-01 13:00:00');
        self::assertTrue($p1->overlaps($p2));

        $p3 = new SessionPeriod('2026-03-01 13:00:00', '2026-03-01 14:00:00');
        self::assertFalse($p1->overlaps($p3));
    }

    public function testEquals(): void
    {
        $p1 = new SessionPeriod('2026-03-01 10:00:00', '2026-03-01 12:00:00');
        $p2 = new SessionPeriod('2026-03-01 10:00:00', '2026-03-01 12:00:00');
        self::assertTrue($p1->equals($p2));
    }

    public function testToArray(): void
    {
        $period = new SessionPeriod('2026-03-01 10:00:00', '2026-03-01 12:00:00');
        $arr = $period->toArray();
        self::assertSame('2026-03-01 10:00:00', $arr['start_time']);
        self::assertSame('2026-03-01 12:00:00', $arr['end_time']);
    }
}
