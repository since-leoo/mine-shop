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

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\MemberSetting;
use App\Domain\Member\Repository\MemberLevelRepository;
use App\Domain\Member\Repository\MemberRepository;
use App\Domain\Member\Repository\MemberWalletTransactionRepository;
use App\Domain\Member\Service\DomainMemberPointsService;
use App\Domain\Member\Service\DomainMemberWalletService;
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Feature: member-vip-level, Property 9: 消费返积分计算公式.
 *
 * Validates: Requirements 5.2, 5.4, 6.2
 *
 * For any payAmountCents (实付金额，单位：分), pointsRatio (系统积分比率) and pointRate (等级积分倍率),
 * the result must equal floor(payAmountCents / 100 * pointsRatio * pointRate).
 * Additionally: result is always non-negative, result is always an integer (floor),
 * and result is 0 when payAmountCents is 0.
 * @internal
 * @coversNothing
 */
final class DomainMemberPointsServicePurchasePointsTest extends TestCase
{
    use TestTrait;

    /**
     * Property 9: For any payAmountCents, pointsRatio and pointRate,
     * calculatePurchasePoints must equal floor(payAmountCents / 100 * pointsRatio * pointRate).
     */
    public function testCalculatePurchasePointsFormulaProperty(): void
    {
        $this->limitTo(200);

        $this->forAll(
            Generators::choose(0, 10000000),  // payAmountCents: 0 to 100,000 yuan
            Generators::choose(1, 500),       // pointsRatio: 1 to 500
            Generators::choose(10, 50),       // pointRate * 10 (to get 1.0 - 5.0 range)
        )->then(function (int $payAmountCents, int $pointsRatio, int $pointRateTenths) {
            $pointRate = $pointRateTenths / 10.0;
            $service = $this->buildService($pointsRatio);

            $result = $service->calculatePurchasePoints($payAmountCents, $pointRate);
            $expected = (int) floor($payAmountCents / 100 * $pointsRatio * $pointRate);

            $this->assertSame(
                $expected,
                $result,
                \sprintf(
                    'Formula mismatch: payAmountCents=%d, pointsRatio=%d, pointRate=%.1f → expected=%d, got=%d',
                    $payAmountCents,
                    $pointsRatio,
                    $pointRate,
                    $expected,
                    $result,
                ),
            );
        });
    }

    /**
     * Property 9 (non-negative): The result is always non-negative for non-negative inputs.
     */
    public function testCalculatePurchasePointsAlwaysNonNegative(): void
    {
        $this->limitTo(200);

        $this->forAll(
            Generators::choose(0, 10000000),  // payAmountCents
            Generators::choose(1, 500),       // pointsRatio
            Generators::choose(10, 50),       // pointRate * 10
        )->then(function (int $payAmountCents, int $pointsRatio, int $pointRateTenths) {
            $pointRate = $pointRateTenths / 10.0;
            $service = $this->buildService($pointsRatio);

            $result = $service->calculatePurchasePoints($payAmountCents, $pointRate);

            $this->assertGreaterThanOrEqual(
                0,
                $result,
                \sprintf(
                    'Result must be non-negative: payAmountCents=%d, pointsRatio=%d, pointRate=%.1f → got=%d',
                    $payAmountCents,
                    $pointsRatio,
                    $pointRate,
                    $result,
                ),
            );
        });
    }

    /**
     * Property 9 (integer floor): The result is always an integer equal to floor of the
     * continuous formula, meaning it never exceeds the continuous value.
     */
    public function testCalculatePurchasePointsIsFlooredInteger(): void
    {
        $this->limitTo(200);

        $this->forAll(
            Generators::choose(0, 10000000),
            Generators::choose(1, 500),
            Generators::choose(10, 50),
        )->then(function (int $payAmountCents, int $pointsRatio, int $pointRateTenths) {
            $pointRate = $pointRateTenths / 10.0;
            $service = $this->buildService($pointsRatio);

            $result = $service->calculatePurchasePoints($payAmountCents, $pointRate);
            $continuousValue = $payAmountCents / 100 * $pointsRatio * $pointRate;

            // Result must be an integer (it's typed as int, but verify the floor property)
            $this->assertSame($result, (int) $result, 'Result must be an integer');

            // Result must not exceed the continuous value
            $this->assertLessThanOrEqual(
                $continuousValue,
                (float) $result,
                \sprintf(
                    'Floored result (%d) must not exceed continuous value (%.4f)',
                    $result,
                    $continuousValue,
                ),
            );

            // Result must be within 1 of the continuous value (floor property)
            $this->assertGreaterThan(
                $continuousValue - 1.0,
                (float) $result,
                \sprintf(
                    'Floored result (%d) must be within 1 of continuous value (%.4f)',
                    $result,
                    $continuousValue,
                ),
            );
        });
    }

    /**
     * Property 9 (zero amount): When payAmountCents is 0, result is always 0
     * regardless of pointsRatio and pointRate.
     */
    public function testCalculatePurchasePointsZeroAmountAlwaysZero(): void
    {
        $this->limitTo(100);

        $this->forAll(
            Generators::choose(1, 500),  // pointsRatio
            Generators::choose(10, 50),  // pointRate * 10
        )->then(function (int $pointsRatio, int $pointRateTenths) {
            $pointRate = $pointRateTenths / 10.0;
            $service = $this->buildService($pointsRatio);

            $result = $service->calculatePurchasePoints(0, $pointRate);

            $this->assertSame(
                0,
                $result,
                \sprintf(
                    'Zero payAmountCents must yield 0 points: pointsRatio=%d, pointRate=%.1f → got=%d',
                    $pointsRatio,
                    $pointRate,
                    $result,
                ),
            );
        });
    }

    private function buildService(int $pointsRatio): DomainMemberPointsService
    {
        $walletService = $this->createMock(DomainMemberWalletService::class);
        $mallSettingService = $this->createMock(DomainMallSettingService::class);
        $memberRepository = $this->createMock(MemberRepository::class);
        $levelRepository = $this->createMock(MemberLevelRepository::class);
        $transactionRepository = $this->createMock(MemberWalletTransactionRepository::class);

        $memberSetting = new MemberSetting(
            enableGrowth: true,
            registerPoints: 100,
            signInReward: 5,
            inviteReward: 50,
            pointsExpireMonths: 24,
            vipLevels: [],
            defaultLevel: 1,
            pointsRatio: $pointsRatio,
        );

        $mallSettingService->method('member')->willReturn($memberSetting);

        return new DomainMemberPointsService(
            $walletService,
            $mallSettingService,
            $memberRepository,
            $levelRepository,
            $transactionRepository,
        );
    }
}
