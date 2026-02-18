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

namespace HyperfTests\Unit\Domain\Member\ValueObject;

use App\Domain\Member\ValueObject\BalanceChangeVo;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class BalanceChangeVoTest extends TestCase
{
    public function testSuccess(): void
    {
        $vo = BalanceChangeVo::success(1, 'balance', 10000, 15000, 5000);
        self::assertSame(1, $vo->memberId);
        self::assertSame('balance', $vo->walletType);
        self::assertSame(10000, $vo->beforeBalance);
        self::assertSame(15000, $vo->afterBalance);
        self::assertSame(5000, $vo->changeAmount);
        self::assertTrue($vo->success);
        self::assertSame('余额变更成功', $vo->message);
    }

    public function testFail(): void
    {
        $vo = BalanceChangeVo::fail('余额不足');
        self::assertFalse($vo->success);
        self::assertSame('余额不足', $vo->message);
        self::assertSame(0, $vo->memberId);
    }
}
