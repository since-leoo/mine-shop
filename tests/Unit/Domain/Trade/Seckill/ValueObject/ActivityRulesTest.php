<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\Seckill\ValueObject;

use App\Domain\Trade\Seckill\ValueObject\ActivityRules;
use PHPUnit\Framework\TestCase;

class ActivityRulesTest extends TestCase
{
    public function testDefaultRules(): void
    {
        $rules = ActivityRules::default();
        $this->assertSame(1, $rules->getMaxQuantityPerUser());
        $this->assertSame(1, $rules->getMinPurchaseQuantity());
        $this->assertFalse($rules->isRequireMemberLevel());
        $this->assertFalse($rules->isAllowRefund());
        $this->assertSame(24, $rules->getRefundDeadlineHours());
    }

    public function testCustomRules(): void
    {
        $rules = new ActivityRules([
            'max_quantity_per_user' => 5,
            'min_purchase_quantity' => 2,
            'require_member_level' => true,
            'required_member_level_id' => 3,
            'allow_refund' => true,
            'refund_deadline_hours' => 48,
        ]);
        $this->assertSame(5, $rules->getMaxQuantityPerUser());
        $this->assertSame(2, $rules->getMinPurchaseQuantity());
        $this->assertTrue($rules->isRequireMemberLevel());
        $this->assertSame(3, $rules->getRequiredMemberLevelId());
        $this->assertTrue($rules->isAllowRefund());
        $this->assertSame(48, $rules->getRefundDeadlineHours());
    }

    public function testMinCannotExceedMax(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ActivityRules(['max_quantity_per_user' => 1, 'min_purchase_quantity' => 5]);
    }

    public function testRequireLevelWithoutIdThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ActivityRules(['require_member_level' => true]);
    }

    public function testCanUserPurchase(): void
    {
        $rules = new ActivityRules(['max_quantity_per_user' => 3, 'min_purchase_quantity' => 1]);
        $this->assertTrue($rules->canUserPurchase(2));
        $this->assertFalse($rules->canUserPurchase(4));
        $this->assertFalse($rules->canUserPurchase(0));
    }

    public function testCanUserPurchaseWithMemberLevel(): void
    {
        $rules = new ActivityRules([
            'max_quantity_per_user' => 5,
            'min_purchase_quantity' => 1,
            'require_member_level' => true,
            'required_member_level_id' => 3,
        ]);
        $this->assertTrue($rules->canUserPurchase(2, 3));
        $this->assertFalse($rules->canUserPurchase(2, 1));
    }

    public function testToArray(): void
    {
        $rules = ActivityRules::default();
        $arr = $rules->toArray();
        $this->assertArrayHasKey('max_quantity_per_user', $arr);
        $this->assertArrayHasKey('min_purchase_quantity', $arr);
        $this->assertArrayHasKey('allow_refund', $arr);
    }
}
