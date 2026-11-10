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

namespace HyperfTests\Unit\Distribution;

use PHPUnit\Framework\TestCase;
use Plugin\Distribution\Domain\ValueObject\DistributionPolicyVo;

/**
 * @internal
 * @coversNothing
 */
final class DistributionPolicyTest extends TestCase
{
    public function testDiscountedGoodsAllocationKeepsEveryCentAndZeroWeightItemsReceiveNothing(): void
    {
        self::assertSame([33, 34, 34], DistributionPolicyVo::allocate(101, [333, 333, 334]));
        self::assertSame([0, 3, 4], DistributionPolicyVo::allocate(7, [0, 100, 100]));
        self::assertSame([0, 0], DistributionPolicyVo::allocate(0, [0, 0]));
        foreach ([1, 7, 99, 1001, 99999] as $goods) {
            $allocated = DistributionPolicyVo::allocate($goods, [1, 17, 83, 0]);
            self::assertSame($goods, array_sum($allocated));
            self::assertSame(0, $allocated[3]);
            foreach ($allocated as $amount) {
                self::assertGreaterThanOrEqual(0, $amount);
            }
        }
    }

    public function testCannotAllocatePositiveGoodsAmountWithoutAValidItemWeight(): void
    {
        $this->expectException(\DomainException::class);
        DistributionPolicyVo::allocate(1, [0, 0]);
    }

    public function testNegativeWeightCannotCreateAnArtificialCommissionBase(): void
    {
        $this->expectException(\DomainException::class);
        DistributionPolicyVo::allocate(100, [200, -100]);
    }

    public function testSkuOverridesProductRegardlessOfConfigurationOrderAndExclusionsWin(): void
    {
        $settings = DistributionPolicyVo::defaults();
        $settings['commission_rate_bps'] = 1000;
        $product = ['product_id' => 7, 'sku_id' => null, 'mode' => 'rate', 'value' => 2000];
        $sku = ['product_id' => 7, 'sku_id' => 21, 'mode' => 'fixed', 'value' => 33];
        foreach ([[$product, $sku], [$sku, $product]] as $rules) {
            $settings['product_rules'] = $rules;
            self::assertSame(66, DistributionPolicyVo::commission($settings, 7, 21, 2, 1000));
            self::assertSame(200, DistributionPolicyVo::commission($settings, 7, 22, 2, 1000));
            self::assertSame(100, DistributionPolicyVo::commission($settings, 8, 23, 2, 1000));
        }
        $settings['excluded_product_ids'] = [7];
        self::assertSame(0, DistributionPolicyVo::commission($settings, 7, 21, 2, 1000));
    }

    public function testFixedPerItemCommissionCannotExceedTheActualPaidGoodsAmount(): void
    {
        $settings = DistributionPolicyVo::defaults();
        $settings['product_rules'] = [['product_id' => 7, 'mode' => 'fixed', 'value' => 200]];
        self::assertSame(600, DistributionPolicyVo::commission($settings, 7, 21, 3, 1000));
        self::assertSame(500, DistributionPolicyVo::commission($settings, 7, 21, 3, 500));
        self::assertSame(0, DistributionPolicyVo::commission($settings, 7, 21, 3, 0));
    }

    public function testRepeatedAndPartialRefundsConvergeToTheOriginalCommissionWithoutRoundingLeakage(): void
    {
        $original = 3;
        $alreadyReversed = 0;
        $deltas = [];
        foreach ([1, 1, 33, 34, 99, 100, 150] as $cumulativeGoodsRefund) {
            $target = DistributionPolicyVo::refundCommission($original, 100, $cumulativeGoodsRefund);
            $delta = $target - $alreadyReversed;
            self::assertGreaterThanOrEqual(0, $delta);
            self::assertLessThanOrEqual($original, $target);
            $deltas[] = $delta;
            $alreadyReversed = $target;
        }
        self::assertSame([1, 0, 0, 1, 1, 0, 0], $deltas);
        self::assertSame($original, array_sum($deltas));
        self::assertSame(0, DistributionPolicyVo::refundCommission($original, 100, 0));
    }

    public function testDuplicateWholeProductRulesCannotHideBehindNullOrZeroSkuIds(): void
    {
        $this->expectException(\DomainException::class);
        DistributionPolicyVo::validate(['product_rules' => [
            ['product_id' => 7, 'sku_id' => null, 'mode' => 'rate', 'value' => 500],
            ['product_id' => 7, 'sku_id' => 0, 'mode' => 'fixed', 'value' => 10],
        ]]);
    }

    public function testAWithdrawalFeeCannotConsumeTheWholePayout(): void
    {
        $this->expectException(\DomainException::class);
        DistributionPolicyVo::validate(['withdrawal_fee_bps' => 10000]);
    }
}
