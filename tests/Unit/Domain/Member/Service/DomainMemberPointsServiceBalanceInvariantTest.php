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

namespace HyperfTests\Unit\Domain\Member\Service;

use App\Domain\Member\Entity\MemberWalletEntity;
use App\Infrastructure\Exception\System\BusinessException;
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Feature: member-vip-level, Property 5: 积分余额非负不变量.
 *
 * Validates: Requirements 3.3, 3.5
 *
 * For any points wallet and any sequence of operations (grant, deduct),
 * the balance must be a non-negative integer at all times.
 * When a deduction amount exceeds the current balance, the operation must be rejected.
 * @internal
 * @coversNothing
 */
final class DomainMemberPointsServiceBalanceInvariantTest extends TestCase
{
    use TestTrait;

    /**
     * Property 5: After any sequence of add/deduct operations (where deductions
     * are capped at current balance, mimicking deductPurchasePoints behavior),
     * the wallet balance is always non-negative.
     *
     * Validates: Requirements 3.3, 3.5
     */
    public function testBalanceNeverNegativeAfterOperationSequence(): void
    {
        $this->limitTo(200);

        $this->forAll(
            Generators::seq(Generators::choose(-5000, 10000)),  // sequence of change amounts
            Generators::choose(0, 10000),                        // initial balance
        )->then(function (array $operations, int $initialBalance) {
            $wallet = $this->makeWalletEntity($initialBalance);

            foreach ($operations as $amount) {
                if ($amount === 0) {
                    continue; // changeBalance rejects zero
                }

                if ($amount > 0) {
                    // Grant operation: add points
                    $wallet->setChangeBalance($amount);
                    $wallet->changeBalance();
                } else {
                    // Deduct operation: cap at current balance (like deductPurchasePoints)
                    $actualDeduction = min(abs($amount), $wallet->getBalance());
                    if ($actualDeduction <= 0) {
                        continue;
                    }
                    $wallet->setChangeBalance(-$actualDeduction);
                    $wallet->changeBalance();
                }

                // Invariant: balance must be non-negative at every step
                $this->assertGreaterThanOrEqual(
                    0,
                    $wallet->getBalance(),
                    \sprintf(
                        'Balance went negative! balance=%d after operation=%d',
                        $wallet->getBalance(),
                        $amount,
                    ),
                );
            }
        });
    }

    /**
     * Property 5: The entity itself rejects deductions that would make balance negative.
     * For any wallet with a non-negative balance, attempting to deduct more than the
     * balance must throw a BusinessException.
     *
     * Validates: Requirements 3.3, 3.5
     */
    public function testEntityRejectsDeductionExceedingBalance(): void
    {
        $this->limitTo(200);

        $this->forAll(
            Generators::choose(0, 10000),  // current balance
            Generators::choose(1, 10000),  // deduction amount (positive, will be negated)
        )->when(static function (int $balance, int $deduction) {
            return $deduction > $balance;
        })->then(function (int $balance, int $deduction) {
            $wallet = $this->makeWalletEntity($balance);

            $wallet->setChangeBalance(-$deduction);

            $this->expectException(BusinessException::class);
            $wallet->changeBalance();
        });
    }

    /**
     * Property 5: After any single grant (positive) operation on a non-negative balance,
     * the resulting balance is still non-negative and equals before + amount.
     *
     * Validates: Requirements 3.5
     */
    public function testGrantOperationMaintainsNonNegativeBalance(): void
    {
        $this->limitTo(200);

        $this->forAll(
            Generators::choose(0, 100000),  // initial balance
            Generators::choose(1, 100000),  // grant amount
        )->then(function (int $initialBalance, int $grantAmount) {
            $wallet = $this->makeWalletEntity($initialBalance);

            $wallet->setChangeBalance($grantAmount);
            $wallet->changeBalance();

            $this->assertSame(
                $initialBalance + $grantAmount,
                $wallet->getBalance(),
                'Balance after grant must equal initial + grant amount',
            );
            $this->assertGreaterThanOrEqual(0, $wallet->getBalance());
        });
    }

    /**
     * Property 5: After a valid deduction (amount <= balance), the resulting balance
     * is non-negative and equals before - amount.
     *
     * Validates: Requirements 3.3, 3.5
     */
    public function testValidDeductionMaintainsNonNegativeBalance(): void
    {
        $this->limitTo(200);

        $this->forAll(
            Generators::choose(1, 100000),  // initial balance (at least 1 for valid deduction)
        )->then(function (int $balance) {
            // Generate a deduction that is guaranteed <= balance
            $deduction = random_int(1, $balance);
            $wallet = $this->makeWalletEntity($balance);

            $wallet->setChangeBalance(-$deduction);
            $wallet->changeBalance();

            $this->assertSame(
                $balance - $deduction,
                $wallet->getBalance(),
                'Balance after deduction must equal initial - deduction',
            );
            $this->assertGreaterThanOrEqual(0, $wallet->getBalance());
        });
    }

    private function makeWalletEntity(int $initialBalance = 0): MemberWalletEntity
    {
        $entity = new MemberWalletEntity();
        $entity->setMemberId(1);
        $entity->setType('points');
        $entity->setBalance($initialBalance);
        $entity->setId(1);
        return $entity;
    }
}
