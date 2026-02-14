<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\GroupBuy\ValueObject;

use App\Domain\Trade\GroupBuy\ValueObject\ActivityTimeVo;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class ActivityTimeVoTest extends TestCase
{
    public function testValid(): void
    {
        $vo = new ActivityTimeVo('2026-03-01 00:00:00', '2026-03-10 00:00:00');
        $this->assertSame('2026-03-01 00:00:00', $vo->getStartTime());
        $this->assertSame('2026-03-10 00:00:00', $vo->getEndTime());
    }

    public function testDurationExceeds30DaysThrows(): void
    {
        $this->expectException(\DomainException::class);
        new ActivityTimeVo('2026-03-01 00:00:00', '2026-05-01 00:00:00');
    }

    public function testIsActive(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-05 12:00:00'));
        $vo = new ActivityTimeVo('2026-03-01 00:00:00', '2026-03-10 00:00:00');
        $this->assertTrue($vo->isActive());
        Carbon::setTestNow();
    }

    public function testIsPending(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-02-28 00:00:00'));
        $vo = new ActivityTimeVo('2026-03-01 00:00:00', '2026-03-10 00:00:00');
        $this->assertTrue($vo->isPending());
        Carbon::setTestNow();
    }

    public function testIsEnded(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-11 00:00:00'));
        $vo = new ActivityTimeVo('2026-03-01 00:00:00', '2026-03-10 00:00:00');
        $this->assertTrue($vo->isEnded());
        Carbon::setTestNow();
    }

    public function testGetRemainingSeconds(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-09 23:59:00'));
        $vo = new ActivityTimeVo('2026-03-01 00:00:00', '2026-03-10 00:00:00');
        $this->assertSame(60, $vo->getRemainingSeconds());
        Carbon::setTestNow();
    }

    public function testGetRemainingSecondsEnded(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-11 00:00:00'));
        $vo = new ActivityTimeVo('2026-03-01 00:00:00', '2026-03-10 00:00:00');
        $this->assertSame(0, $vo->getRemainingSeconds());
        Carbon::setTestNow();
    }
}
