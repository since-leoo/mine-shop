<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\Seckill\ValueObject;

use App\Domain\Trade\Seckill\ValueObject\SessionRules;
use PHPUnit\Framework\TestCase;

class SessionRulesTest extends TestCase
{
    public function testDefaultRules(): void
    {
        $rules = SessionRules::default();
        $this->assertSame(1, $rules->getMaxQuantityPerUser());
        $this->assertSame(0, $rules->getTotalQuantity());
        $this->assertFalse($rules->isAllowOverSell());
    }

    public function testCustomRules(): void
    {
        $rules = new SessionRules([
            'max_quantity_per_user' => 3,
            'total_quantity' => 100,
            'allow_over_sell' => true,
        ]);
        $this->assertSame(3, $rules->getMaxQuantityPerUser());
        $this->assertSame(100, $rules->getTotalQuantity());
        $this->assertTrue($rules->isAllowOverSell());
    }

    public function testCanPurchase(): void
    {
        $rules = new SessionRules(['max_quantity_per_user' => 3]);
        $this->assertTrue($rules->canPurchase(2, 0));
        $this->assertTrue($rules->canPurchase(1, 2));
        $this->assertFalse($rules->canPurchase(2, 2));
    }

    public function testToArray(): void
    {
        $rules = new SessionRules(['max_quantity_per_user' => 5, 'total_quantity' => 50]);
        $arr = $rules->toArray();
        $this->assertSame(5, $arr['max_quantity_per_user']);
        $this->assertSame(50, $arr['total_quantity']);
    }
}
