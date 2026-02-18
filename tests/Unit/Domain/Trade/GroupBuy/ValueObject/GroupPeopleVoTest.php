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

namespace HyperfTests\Unit\Domain\Trade\GroupBuy\ValueObject;

use App\Domain\Trade\GroupBuy\ValueObject\GroupPeopleVo;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class GroupPeopleVoTest extends TestCase
{
    public function testValid(): void
    {
        $vo = new GroupPeopleVo(2, 10);
        self::assertSame(2, $vo->getMinPeople());
        self::assertSame(10, $vo->getMaxPeople());
    }

    public function testMaxLessThanMinThrows(): void
    {
        $this->expectException(\DomainException::class);
        new GroupPeopleVo(10, 2);
    }

    public function testEqualIsAllowed(): void
    {
        $vo = new GroupPeopleVo(5, 5);
        self::assertSame(5, $vo->getMinPeople());
    }

    public function testCanFormGroup(): void
    {
        $vo = new GroupPeopleVo(2, 10);
        self::assertFalse($vo->canFormGroup(1));
        self::assertTrue($vo->canFormGroup(2));
        self::assertTrue($vo->canFormGroup(5));
        self::assertTrue($vo->canFormGroup(10));
        self::assertFalse($vo->canFormGroup(11));
    }
}
