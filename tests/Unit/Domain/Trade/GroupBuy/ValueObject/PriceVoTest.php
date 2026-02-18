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

use App\Domain\Trade\GroupBuy\ValueObject\PriceVo;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class PriceVoTest extends TestCase
{
    public function testValidPrice(): void
    {
        $vo = new PriceVo(10000, 8000);
        self::assertSame(10000, $vo->getOriginalPrice());
        self::assertSame(8000, $vo->getGroupPrice());
    }

    public function testGroupPriceMustBeLessThanOriginal(): void
    {
        $this->expectException(\DomainException::class);
        new PriceVo(8000, 10000);
    }

    public function testEqualPriceThrows(): void
    {
        $this->expectException(\DomainException::class);
        new PriceVo(5000, 5000);
    }

    public function testGetDiscountRate(): void
    {
        $vo = new PriceVo(10000, 8000);
        self::assertSame(80.0, $vo->getDiscountRate());
    }

    public function testGetDiscountAmount(): void
    {
        $vo = new PriceVo(10000, 8000);
        self::assertSame(2000, $vo->getDiscountAmount());
    }
}
