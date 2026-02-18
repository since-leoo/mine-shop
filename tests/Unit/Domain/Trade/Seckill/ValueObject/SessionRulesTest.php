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

use App\Domain\Trade\Seckill\ValueObject\SessionRules;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class SessionRulesTest extends TestCase
{
    public function testDefaultRules(): void
    {
        $rules = SessionRules::default();
        self::assertSame(1, $rules->getMaxQuantityPerUser());
        self::assertSame(0, $rules->getTotalQuantity());
        self::assertFalse($rules->isAllowOverSell());
    }

    public function testCustomRules(): void
    {
        $rules = new SessionRules([
            'max_quantity_per_user' => 3,
            'total_quantity' => 100,
            'allow_over_sell' => true,
        ]);
        self::assertSame(3, $rules->getMaxQuantityPerUser());
        self::assertSame(100, $rules->getTotalQuantity());
        self::assertTrue($rules->isAllowOverSell());
    }

    public function testCanPurchase(): void
    {
        $rules = new SessionRules(['max_quantity_per_user' => 3]);
        self::assertTrue($rules->canPurchase(2, 0));
        self::assertTrue($rules->canPurchase(1, 2));
        self::assertFalse($rules->canPurchase(2, 2));
    }

    public function testToArray(): void
    {
        $rules = new SessionRules(['max_quantity_per_user' => 5, 'total_quantity' => 50]);
        $arr = $rules->toArray();
        self::assertSame(5, $arr['max_quantity_per_user']);
        self::assertSame(50, $arr['total_quantity']);
    }
}
