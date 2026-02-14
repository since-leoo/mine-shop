<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Member\ValueObject;

use App\Domain\Member\ValueObject\BalanceChangeVo;
use PHPUnit\Framework\TestCase;

class BalanceChangeVoTest extends TestCase
{
    public function testSuccess(): void
    {
        $vo = BalanceChangeVo::success(1, 'balance', 10000, 15000, 5000);
        $this->assertSame(1, $vo->memberId);
        $this->assertSame('balance', $vo->walletType);
        $this->assertSame(10000, $vo->beforeBalance);
        $this->assertSame(15000, $vo->afterBalance);
        $this->assertSame(5000, $vo->changeAmount);
        $this->assertTrue($vo->success);
        $this->assertSame('余额变更成功', $vo->message);
    }

    public function testFail(): void
    {
        $vo = BalanceChangeVo::fail('余额不足');
        $this->assertFalse($vo->success);
        $this->assertSame('余额不足', $vo->message);
        $this->assertSame(0, $vo->memberId);
    }
}
