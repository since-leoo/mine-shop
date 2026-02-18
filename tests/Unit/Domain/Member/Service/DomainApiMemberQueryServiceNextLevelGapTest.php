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

use App\Domain\Member\Api\Query\DomainApiMemberQueryService;
use App\Domain\Member\Repository\MemberRepository;
use App\Domain\Member\Service\DomainMemberLevelService;
use App\Infrastructure\Model\Member\Member;
use App\Infrastructure\Model\Member\MemberLevel;
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Feature: member-vip-level, Property 11: 下一等级差值计算.
 *
 * Validates: Requirements 7.3
 *
 * For any member's current growth value and level configuration list,
 * if there's a higher level, the returned gap must equal
 * next_level.growth_value_min - current_growth_value;
 * if already at max level, gap = 0.
 * @internal
 * @coversNothing
 */
final class DomainApiMemberQueryServiceNextLevelGapTest extends TestCase
{
    use TestTrait;

    /**
     * Property 11 (core): For any growth value and level config list,
     * if there's a higher level, next_level_gap = next_level.growth_value_min - current_growth_value;
     * if already at max level, next_level_gap = 0.
     */
    public function testNextLevelGapCalculation(): void
    {
        $this->limitTo(200);

        $this->forAll(
            Generators::choose(2, 8),       // number of levels (at least 2 to have a "next")
            Generators::choose(0, 100000),  // growth value
        )->then(function (int $count, int $growthValue) {
            $levels = $this->generateValidLevels($count);
            $expectedGap = $this->computeExpectedGap($levels, $growthValue);

            $service = $this->buildService($levels, 1, $growthValue);
            $result = $service->getVipInfo(1);

            $this->assertSame(
                $expectedGap,
                $result['next_level_gap'],
                \sprintf(
                    'Gap mismatch: growthValue=%d, levels=%d, expected=%d, got=%d',
                    $growthValue,
                    $count,
                    $expectedGap,
                    $result['next_level_gap'],
                ),
            );
        });
    }

    /**
     * Property 11 (max level): When growth value is at or above the highest level's threshold,
     * the gap must be 0.
     */
    public function testMaxLevelGapIsZero(): void
    {
        $this->limitTo(100);

        $this->forAll(
            Generators::choose(1, 8),       // number of levels
            Generators::choose(0, 50000),   // extra growth above max threshold
        )->then(function (int $count, int $extraGrowth) {
            $levels = $this->generateValidLevels($count);
            $maxThreshold = $levels[$count - 1]['growth_value_min'];
            $growthValue = $maxThreshold + $extraGrowth;

            $service = $this->buildService($levels, 1, $growthValue);
            $result = $service->getVipInfo(1);

            $this->assertSame(
                0,
                $result['next_level_gap'],
                \sprintf(
                    'Max level gap must be 0: growthValue=%d (max threshold=%d), got=%d',
                    $growthValue,
                    $maxThreshold,
                    $result['next_level_gap'],
                ),
            );
        });
    }

    /**
     * Property 11 (non-negative): The gap must always be non-negative.
     */
    public function testGapIsAlwaysNonNegative(): void
    {
        $this->limitTo(200);

        $this->forAll(
            Generators::choose(1, 8),
            Generators::choose(0, 100000),
        )->then(function (int $count, int $growthValue) {
            $levels = $this->generateValidLevels($count);

            $service = $this->buildService($levels, 1, $growthValue);
            $result = $service->getVipInfo(1);

            $this->assertGreaterThanOrEqual(
                0,
                $result['next_level_gap'],
                \sprintf(
                    'Gap must be non-negative: growthValue=%d, got=%d',
                    $growthValue,
                    $result['next_level_gap'],
                ),
            );
        });
    }

    /**
     * Property 11 (exact threshold): When growth value exactly equals a level's threshold
     * (not the max level), the gap should equal the difference to the next level's threshold.
     */
    public function testExactThresholdGapEqualsNextLevelDifference(): void
    {
        $this->limitTo(100);

        $this->forAll(
            Generators::choose(2, 8),  // at least 2 levels
            Generators::choose(0, 6),  // index of level to test (not the last one)
        )->then(function (int $count, int $targetIdx) {
            // Ensure targetIdx is not the last level
            $targetIdx %= ($count - 1);
            $levels = $this->generateValidLevels($count);
            $growthValue = $levels[$targetIdx]['growth_value_min'];

            $expectedGap = $levels[$targetIdx + 1]['growth_value_min'] - $growthValue;

            $service = $this->buildService($levels, 1, $growthValue);
            $result = $service->getVipInfo(1);

            $this->assertSame(
                $expectedGap,
                $result['next_level_gap'],
                \sprintf(
                    'At exact threshold of level %d (growth=%d), gap to next level should be %d, got %d',
                    $levels[$targetIdx]['level'],
                    $growthValue,
                    $expectedGap,
                    $result['next_level_gap'],
                ),
            );
        });
    }

    /**
     * Generate a valid list of level configs with strictly increasing growth_value_min.
     * Level 1 always starts at growth_value_min = 0.
     *
     * @return array<int, array{level: int, growth_value_min: int, name: string, icon: null|string, privileges: array}>
     */
    private function generateValidLevels(int $count): array
    {
        $levels = [];
        $currentMin = 0;

        for ($i = 0; $i < $count; ++$i) {
            $levels[] = [
                'level' => $i + 1,
                'growth_value_min' => $currentMin,
                'name' => 'VIP' . ($i + 1),
                'icon' => null,
                'privileges' => [],
            ];
            $currentMin += random_int(100, 5000);
        }

        return $levels;
    }

    /**
     * Create a MemberLevel mock from level data.
     */
    private function makeLevelMock(array $data): MemberLevel
    {
        $mock = $this->getMockBuilder(MemberLevel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute'])
            ->getMock();

        $mock->method('getAttribute')
            ->willReturnCallback(static fn (string $key) => $data[$key] ?? null);

        return $mock;
    }

    /**
     * Create a Member mock with the given growth value.
     */
    private function makeMemberMock(int $id, int $growthValue): Member
    {
        $mock = $this->getMockBuilder(Member::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute'])
            ->getMock();

        $mock->method('getAttribute')
            ->willReturnCallback(static fn (string $key) => match ($key) {
                'id' => $id,
                'growth_value' => $growthValue,
                default => null,
            });

        return $mock;
    }

    /**
     * Independently compute the expected matched level number for a growth value.
     * Returns the highest level number where growth_value_min <= growthValue.
     */
    private function computeMatchedLevel(array $levels, int $growthValue): array
    {
        $qualifying = array_filter($levels, static fn (array $l) => $l['growth_value_min'] <= $growthValue);
        usort($qualifying, static fn (array $a, array $b) => $b['level'] <=> $a['level']);
        return $qualifying[0];
    }

    /**
     * Independently compute the expected next level gap.
     */
    private function computeExpectedGap(array $levels, int $growthValue): int
    {
        $currentLevel = $this->computeMatchedLevel($levels, $growthValue);

        // Find the first level with a higher level number
        $sortedLevels = $levels;
        usort($sortedLevels, static fn (array $a, array $b) => $a['level'] <=> $b['level']);

        foreach ($sortedLevels as $level) {
            if ($level['level'] > $currentLevel['level']) {
                return max($level['growth_value_min'] - $growthValue, 0);
            }
        }

        // Already at max level
        return 0;
    }

    /**
     * Build the service with mocked dependencies for the given levels and growth value.
     */
    private function buildService(array $levelData, int $memberId, int $growthValue): DomainApiMemberQueryService
    {
        $testCase = $this;

        // Compute the matched level for matchLevelByGrowthValue
        $matchedLevelData = $this->computeMatchedLevel($levelData, $growthValue);
        $matchedLevelMock = $this->makeLevelMock($matchedLevelData);

        // Build active level mocks for getActiveLevels
        $sortedLevels = $levelData;
        usort($sortedLevels, static fn (array $a, array $b) => $a['level'] <=> $b['level']);
        $activeLevelMocks = array_map(fn (array $l) => $this->makeLevelMock($l), $sortedLevels);

        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->method('findById')
            ->willReturn($this->makeMemberMock($memberId, $growthValue));

        $levelService = $this->createMock(DomainMemberLevelService::class);
        $levelService->method('matchLevelByGrowthValue')
            ->willReturn($matchedLevelMock);
        $levelService->method('getActiveLevels')
            ->willReturn($activeLevelMocks);

        return new DomainApiMemberQueryService($memberRepository, $levelService);
    }
}
