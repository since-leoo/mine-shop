<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\GroupBuy\ValueObject;

use App\Domain\Trade\GroupBuy\ValueObject\GroupPeopleVo;
use PHPUnit\Framework\TestCase;

class GroupPeopleVoTest extends TestCase
{
    public function testValid(): void
    {
        $vo = new GroupPeopleVo(2, 10);
        $this->assertSame(2, $vo->getMinPeople());
        $this->assertSame(10, $vo->getMaxPeople());
    }

    public function testMaxLessThanMinThrows(): void
    {
        $this->expectException(\DomainException::class);
        new GroupPeopleVo(10, 2);
    }

    public function testEqualIsAllowed(): void
    {
        $vo = new GroupPeopleVo(5, 5);
        $this->assertSame(5, $vo->getMinPeople());
    }

    public function testCanFormGroup(): void
    {
        $vo = new GroupPeopleVo(2, 10);
        $this->assertFalse($vo->canFormGroup(1));
        $this->assertTrue($vo->canFormGroup(2));
        $this->assertTrue($vo->canFormGroup(5));
        $this->assertTrue($vo->canFormGroup(10));
        $this->assertFalse($vo->canFormGroup(11));
    }
}
