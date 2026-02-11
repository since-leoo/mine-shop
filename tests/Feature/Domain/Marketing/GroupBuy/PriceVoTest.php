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

namespace HyperfTests\Feature\Domain\Marketing\GroupBuy;

use PHPUnit\Framework\TestCase;
use App\Domain\Trade\GroupBuy\ValueObject\PriceVo;

/**
 * Feature: group-buy-order, Property 1: PriceVo 整数价格不变量.
 *
 * Validates: Requirements 1.1, 1.3, 1.4
 *
 * @internal
 * @coversNothing
 */
final class PriceVoTest extends TestCase
{
    /**
     * Property 1: PriceVo 整数价格不变量.
     *
     * For any 合法的整数价格对（originalPrice > groupPrice > 0），创建 PriceVo 后：
     * - getOriginalPrice() 返回 originalPrice
     * - getGroupPrice() 返回 groupPrice
     * - getDiscountAmount() 等于 originalPrice - groupPrice
     * - getDiscountRate() 等于 round((groupPrice / originalPrice) * 100, 2)
     *
     * **Validates: Requirements 1.1, 1.3, 1.4**
     *
     * @dataProvider providePriceVoIntegerPriceInvariantCases
     */
    public function testPriceVoIntegerPriceInvariant(int $originalPrice, int $groupPrice): void
    {
        $vo = new PriceVo($originalPrice, $groupPrice);

        // Requirement 1.1: originalPrice and groupPrice are stored as int
        self::assertSame($originalPrice, $vo->getOriginalPrice());
        self::assertSame($groupPrice, $vo->getGroupPrice());

        // Requirement 1.4: getDiscountAmount() returns int, direct subtraction without round()
        $expectedDiscountAmount = $originalPrice - $groupPrice;
        self::assertSame($expectedDiscountAmount, $vo->getDiscountAmount());

        // Requirement 1.3: getDiscountRate() returns float percentage
        $expectedDiscountRate = round(($groupPrice / $originalPrice) * 100, 2);
        self::assertSame($expectedDiscountRate, $vo->getDiscountRate());
    }

    /**
     * Generates at least 100 random valid price pairs where originalPrice > groupPrice > 0.
     *
     * @return iterable<string, array{int, int}>
     */
    public static function providePriceVoIntegerPriceInvariantCases(): iterable
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; ++$i) {
            // groupPrice: 1 ~ 999_999 (分), originalPrice: groupPrice+1 ~ groupPrice+1_000_000
            $groupPrice = random_int(1, 999_999);
            $originalPrice = $groupPrice + random_int(1, 1_000_000);

            yield "iteration_{$i} (original={$originalPrice}, group={$groupPrice})" => [
                $originalPrice,
                $groupPrice,
            ];
        }
    }

    /**
     * Edge case: groupPrice equals originalPrice should throw DomainException.
     */
    public function testRejectsGroupPriceEqualToOriginalPrice(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('团购价必须小于原价');

        new PriceVo(100, 100);
    }

    /**
     * Edge case: groupPrice greater than originalPrice should throw DomainException.
     */
    public function testRejectsGroupPriceGreaterThanOriginalPrice(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('团购价必须小于原价');

        new PriceVo(100, 200);
    }

    /**
     * Edge case: minimal valid price pair (originalPrice=2, groupPrice=1).
     */
    public function testMinimalValidPricePair(): void
    {
        $vo = new PriceVo(2, 1);

        self::assertSame(2, $vo->getOriginalPrice());
        self::assertSame(1, $vo->getGroupPrice());
        self::assertSame(1, $vo->getDiscountAmount());
        self::assertSame(50.0, $vo->getDiscountRate());
    }
}
