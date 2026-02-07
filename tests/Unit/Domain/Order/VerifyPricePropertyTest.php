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

namespace HyperfTests\Unit\Domain\Order;

use App\Domain\Trade\Order\Entity\OrderEntity;
use PHPUnit\Framework\TestCase;

/**
 * Feature: order-checkout-refactor, Property 9: 价格校验（分为单位）.
 *
 * For any OrderEntity，当前端传入的 total_amount（分）与后端 payAmount（分）不相等时，
 * verifyPrice() 应抛出 DomainException。当两者相等时，verifyPrice() 应正常通过。
 *
 * 改造后 payAmount 已经是 int（分），verifyPrice() 直接比较两个 int，无需 round() 转换。
 *
 * **Validates: Requirements 10.1, 10.2, 10.3**
 *
 * @internal
 * @coversNothing
 */
final class VerifyPricePropertyTest extends TestCase
{
    private const ITERATIONS = 100;

    /**
     * Property 9a: When frontend cent equals backend payAmount (int, cents), no exception is thrown.
     *
     * For any payAmount (int, cents), passing the same value as frontendAmountCent
     * should NOT throw DomainException.
     */
    public function testMatchingCentsDoesNotThrow(): void
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $payAmountCent = $this->randomPayAmountCent();
            $entity = new OrderEntity();
            $entity->setPayAmount($payAmountCent);

            // Should not throw — both are the same int value
            $entity->verifyPrice($payAmountCent);
        }

        // If we reach here, all 100 iterations passed without exception
        self::assertTrue(true, 'All matching-cent iterations passed without exception');
    }

    /**
     * Property 9b: When frontend cent does NOT match backend payAmount (int, cents),
     * DomainException is thrown.
     *
     * For any payAmount (int, cents), if frontendAmountCent differs,
     * verifyPrice() must throw DomainException.
     */
    public function testMismatchedCentsThrowsDomainException(): void
    {
        $exceptionCount = 0;

        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $payAmountCent = $this->randomPayAmountCent();
            $entity = new OrderEntity();
            $entity->setPayAmount($payAmountCent);

            $mismatchedCent = $this->randomMismatchedCent($payAmountCent);

            try {
                $entity->verifyPrice($mismatchedCent);
                self::fail(\sprintf(
                    'Iteration %d: Expected DomainException for payAmount=%d, frontendCent=%d',
                    $i,
                    $payAmountCent,
                    $mismatchedCent,
                ));
            } catch (\DomainException $e) {
                ++$exceptionCount;
                self::assertSame('商品价格已变动，请重新下单', $e->getMessage());
            }
        }

        self::assertSame(self::ITERATIONS, $exceptionCount, 'All mismatched-cent iterations should throw DomainException');
    }

    /**
     * Property 9c: Int comparison is deterministic — the same payAmount (int)
     * always produces the same result, ensuring deterministic comparison.
     */
    public function testCentComparisonIsDeterministic(): void
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $payAmountCent = $this->randomPayAmountCent();
            $entity = new OrderEntity();
            $entity->setPayAmount($payAmountCent);

            // Both calls should succeed without exception — same int value
            $entity->verifyPrice($payAmountCent);
            $entity->verifyPrice($payAmountCent);
        }

        self::assertTrue(true);
    }

    /**
     * Generate a random pay amount in cents (分).
     * Range: 0 to 9999999 (i.e. ¥0.00 to ¥99999.99).
     */
    private function randomPayAmountCent(): int
    {
        return random_int(0, 9999999);
    }

    /**
     * Generate a frontend cent value that is guaranteed to differ from the backend cent.
     */
    private function randomMismatchedCent(int $backendCent): int
    {
        // Apply a non-zero offset: randomly +1 to +1000 or -1 to -1000
        $offset = random_int(1, 1000);
        if (random_int(0, 1) === 0) {
            $offset = -$offset;
        }

        $mismatched = $backendCent + $offset;

        // Ensure it's truly different
        if ($mismatched === $backendCent) {
            $mismatched = $backendCent + 1;
        }

        return $mismatched;
    }
}
