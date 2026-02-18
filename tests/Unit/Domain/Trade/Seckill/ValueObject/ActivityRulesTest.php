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

use App\Domain\Trade\Seckill\ValueObject\ActivityRules;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class ActivityRulesTest extends TestCase
{
    public function testDefaultRules(): void
    {
        $rules = ActivityRules::default();
        self::assertSame(1, $rules->getMaxQuantityPerUser());
        self::assertSame(1, $rules->getMinPurchaseQuantity());
        self::assertFalse($rules->isRequireMemberLevel());
        self::assertFalse($rules->isAllowRefund());
        self::assertSame(24, $rules->getRefundDeadlineHours());
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
        self::assertSame(5, $rules->getMaxQuantityPerUser());
        self::assertSame(2, $rules->getMinPurchaseQuantity());
        self::assertTrue($rules->isRequireMemberLevel());
        self::assertSame(3, $rules->getRequiredMemberLevelId());
        self::assertTrue($rules->isAllowRefund());
        self::assertSame(48, $rules->getRefundDeadlineHours());
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
        self::assertTrue($rules->canUserPurchase(2));
        self::assertFalse($rules->canUserPurchase(4));
        self::assertFalse($rules->canUserPurchase(0));
    }

    public function testCanUserPurchaseWithMemberLevel(): void
    {
        $rules = new ActivityRules([
            'max_quantity_per_user' => 5,
            'min_purchase_quantity' => 1,
            'require_member_level' => true,
            'required_member_level_id' => 3,
        ]);
        self::assertTrue($rules->canUserPurchase(2, 3));
        self::assertFalse($rules->canUserPurchase(2, 1));
    }

    public function testToArray(): void
    {
        $rules = ActivityRules::default();
        $arr = $rules->toArray();
        self::assertArrayHasKey('max_quantity_per_user', $arr);
        self::assertArrayHasKey('min_purchase_quantity', $arr);
        self::assertArrayHasKey('allow_refund', $arr);
    }
}
