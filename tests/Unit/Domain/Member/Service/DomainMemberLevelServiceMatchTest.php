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
use App\Domain\Member\Repository\MemberLevelRepository;
use App\Domain\Member\Service\DomainMemberLevelService;
use App\Infrastructure\Model\Member\MemberLevel;
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Feature: member-vip-level, Property 3: 成长值与等级匹配.
 *
 * Validates: Requirements 2.3, 2.4
 *
 * For any growth value and level config list, matchLevelByGrowthValue(growthValue)
 * must return the level with the highest level number among all active levels
 * where growth_value_min <= growthValue.
 * @internal
 * @coversNothing
 */
final class DomainMemberLevelServiceMatchTest extends TestCase
{
    use TestTrait;

    /**
     * Property 3 (core): For any growth value and a valid level config list,
     * matchLevelByGrowthValue must return the level with the highest level number
     * among all levels where growth_value_min <= growthValue.
     */
    public function testMatchedLevelIsHighestAmongQualifyingLevels(): void
    {
        $this->limitTo(200);

        $this->forAll(
            Generators::choose(2, 8),       // number of levels
            Generators::choose(0, 100000),  // growth value to test
        )->then(function (int $count, int $growthValue) {
            $levels = $this->generateValidLevels($count);
            $expectedLevelNum = $this->computeExpectedLevelNumber($levels, $growthValue);

            // Level 1 starts at growth_value_min=0, so growthValue >= 0 always qualifies
            $this->assertNotNull($expectedLevelNum);

            $service = $this->buildServiceWithLevels($levels);
            $result = $service->matchLevelByGrowthValue($growthValue);

            $this->assertSame(
                $expectedLevelNum,
                $result->level,
                \sprintf(
                    'For growthValue=%d with %d levels, expected level %d but got level %d',
                    $growthValue,
                    $count,
                    $expectedLevelNum,
                    $result->level,
                ),
            );
        });
    }

    /**
     * Property 3 (boundary): When growthValue exactly equals a level's growth_value_min,
     * that level must be included in the qualifying set (growth_value_min <= growthValue).
     */
    public function testExactThresholdMatchIncludesTargetLevel(): void
    {
        $this->limitTo(200);

        $this->forAll(
            Generators::choose(2, 8),  // number of levels
            Generators::choose(0, 7),  // index of level whose threshold to use
        )->then(function (int $count, int $targetIdx) {
            $targetIdx %= $count;
            $levels = $this->generateValidLevels($count);
            $growthValue = $levels[$targetIdx]['growth_value_min'];

            $expectedLevelNum = $this->computeExpectedLevelNumber($levels, $growthValue);
            $this->assertNotNull($expectedLevelNum);

            $service = $this->buildServiceWithLevels($levels);
            $result = $service->matchLevelByGrowthValue($growthValue);

            // The matched level must be >= the target level (since the target qualifies)
            $this->assertGreaterThanOrEqual(
                $levels[$targetIdx]['level'],
                $result->level,
                \sprintf(
                    'For growthValue=%d (exact threshold of level %d), matched level %d should be >= %d',
                    $growthValue,
                    $levels[$targetIdx]['level'],
                    $result->level,
                    $levels[$targetIdx]['level'],
                ),
            );

            $this->assertSame($expectedLevelNum, $result->level);
        });
    }

    /**
     * Property 3 (upgrade scenario): When growth value increases, the matched level
     * number must be monotonically non-decreasing.
     */
    public function testUpgradeScenarioMatchesHigherOrEqualLevel(): void
    {
        $this->limitTo(100);

        $this->forAll(
            Generators::choose(3, 8),
        )->then(function (int $count) {
            $levels = $this->generateValidLevels($count);
            $service = $this->buildServiceWithLevels($levels);

            // Test with growth value at level 1 threshold
            $lowGrowth = $levels[0]['growth_value_min'];
            $lowResult = $service->matchLevelByGrowthValue($lowGrowth);

            // Test with growth value at or above the last level's threshold
            $highGrowth = $levels[$count - 1]['growth_value_min'] + random_int(0, 10000);
            $highResult = $service->matchLevelByGrowthValue($highGrowth);

            $this->assertGreaterThanOrEqual(
                $lowResult->level,
                $highResult->level,
                \sprintf(
                    'Upgrade: growthValue %d -> level %d, growthValue %d -> level %d (should be >=)',
                    $lowGrowth,
                    $lowResult->level,
                    $highGrowth,
                    $highResult->level,
                ),
            );
        });
    }

    /**
     * Property 3 (downgrade scenario): When growth value decreases below a level's
     * threshold, the matched level must decrease accordingly.
     */
    public function testDowngradeScenarioMatchesLowerOrEqualLevel(): void
    {
        $this->limitTo(100);

        $this->forAll(
            Generators::choose(3, 8),
        )->then(function (int $count) {
            $levels = $this->generateValidLevels($count);
            $service = $this->buildServiceWithLevels($levels);

            // Start at the highest level
            $highGrowth = $levels[$count - 1]['growth_value_min'];
            $highResult = $service->matchLevelByGrowthValue($highGrowth);

            // Drop to just below level 2's threshold (should match level 1)
            if ($levels[1]['growth_value_min'] > 0) {
                $lowGrowth = $levels[1]['growth_value_min'] - 1;
                $lowResult = $service->matchLevelByGrowthValue($lowGrowth);

                $this->assertLessThanOrEqual(
                    $highResult->level,
                    $lowResult->level,
                    \sprintf(
                        'Downgrade: growthValue %d -> level %d, growthValue %d -> level %d (should be <=)',
                        $highGrowth,
                        $highResult->level,
                        $lowGrowth,
                        $lowResult->level,
                    ),
                );
            }
        });
    }

    /**
     * Create a simple value object that behaves like MemberLevel for property access.
     * We use stdClass-like objects stored in an array and the fake builder returns them.
     *
     * @return array{level: int, growth_value_min: int, status: string, name: string, mock: MemberLevel}
     */
    private function makeLevelData(int $levelNumber, int $growthValueMin): array
    {
        return [
            'level' => $levelNumber,
            'growth_value_min' => $growthValueMin,
            'status' => 'active',
            'name' => 'VIP' . $levelNumber,
        ];
    }

    /**
     * Build a DomainMemberLevelService whose matchLevelByGrowthValue query chain
     * is backed by the given in-memory level data.
     *
     * @param array<int, array{level: int, growth_value_min: int, status: string, name: string}> $levelData
     */
    private function buildServiceWithLevels(array $levelData): DomainMemberLevelService
    {
        $testCase = $this;

        // Anonymous class that simulates the query builder chain
        $fakeBuilder = new class($levelData, $testCase) {
            private array $levels;

            private ?int $maxGrowthValue = null;

            private TestCase $testCase;

            public function __construct(array $levels, TestCase $testCase)
            {
                $this->levels = $levels;
                $this->testCase = $testCase;
            }

            public function where(...$args): self
            {
                if (\count($args) === 3 && $args[0] === 'growth_value_min' && $args[1] === '<=') {
                    $this->maxGrowthValue = (int) $args[2];
                }
                return $this;
            }

            public function orderByDesc(string $column): self
            {
                return $this;
            }

            public function first(): ?MemberLevel
            {
                $matching = array_filter($this->levels, function (array $l) {
                    return $l['growth_value_min'] <= $this->maxGrowthValue;
                });

                if (empty($matching)) {
                    return null;
                }

                // Sort by level descending, return first (simulates ORDER BY level DESC LIMIT 1)
                usort($matching, static fn (array $a, array $b) => $b['level'] <=> $a['level']);
                $top = $matching[0];

                // Use a MemberLevel mock with __get configured to return our data
                $levelMock = $this->testCase->getMockBuilder(MemberLevel::class)
                    ->disableOriginalConstructor()
                    ->onlyMethods(['getAttribute'])
                    ->getMock();

                $attrs = $top;
                $levelMock->method('getAttribute')
                    ->willReturnCallback(static function (string $key) use ($attrs) {
                        return $attrs[$key] ?? null;
                    });

                return $levelMock;
            }
        };

        $model = $this->createMock(MemberLevel::class);
        $model->method('newQuery')->willReturn($fakeBuilder);

        $repository = $this->createMock(MemberLevelRepository::class);
        $repository->method('getModel')->willReturn($model);

        $mallSettingService = $this->createMock(DomainMallSettingService::class);

        return new DomainMemberLevelService($repository, $mallSettingService);
    }

    /**
     * Independently compute the expected level number for a given growth value.
     *
     * @param array<int, array{level: int, growth_value_min: int}> $levels
     */
    private function computeExpectedLevelNumber(array $levels, int $growthValue): ?int
    {
        $qualifying = array_filter($levels, static fn (array $l) => $l['growth_value_min'] <= $growthValue);

        if (empty($qualifying)) {
            return null;
        }

        usort($qualifying, static fn (array $a, array $b) => $b['level'] <=> $a['level']);
        return $qualifying[0]['level'];
    }

    /**
     * Generate a valid level data list: unique sequential level numbers,
     * strictly increasing growth_value_min starting from 0.
     *
     * @return array<int, array{level: int, growth_value_min: int, status: string, name: string}>
     */
    private function generateValidLevels(int $count): array
    {
        $levels = [];
        $currentMin = 0;

        for ($i = 0; $i < $count; ++$i) {
            $levels[] = $this->makeLevelData($i + 1, $currentMin);
            $currentMin += random_int(1, 5000);
        }

        return $levels;
    }
}
