<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Member\Service;

use App\Domain\Member\Service\DomainMemberLevelService;
use App\Infrastructure\Exception\System\BusinessException;
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Feature: member-vip-level, Property 1: 等级配置校验——序号唯一且成长值门槛严格递增
 *
 * Validates: Requirements 1.2, 1.3, 1.5
 */
class DomainMemberLevelServiceValidateTest extends TestCase
{
    use TestTrait;

    private DomainMemberLevelService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // validateLevelConfigs is a pure validation method that doesn't use any dependencies,
        // so we can safely create the service with mocked constructor args.
        $repository = $this->createMock(\App\Domain\Member\Repository\MemberLevelRepository::class);
        $mallSettingService = $this->createMock(\App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService::class);

        $this->service = new DomainMemberLevelService($repository, $mallSettingService);
    }

    /**
     * Property 1 (positive): For any list of level configs with unique level numbers
     * and strictly increasing growth_value_min (sorted by level), validateLevelConfigs
     * must NOT throw an exception.
     */
    public function testValidConfigsWithUniqueLevelsAndStrictlyIncreasingThresholdsShouldPass(): void
    {
        $this->limitTo(100);

        $this->forAll(
            Generators::choose(1, 10), // number of levels (1..10)
        )->then(function (int $count) {
            // Generate unique level numbers and strictly increasing growth_value_min
            $levels = [];
            $currentGrowthMin = 0;

            for ($i = 0; $i < $count; ++$i) {
                $levelNumber = $i + 1; // unique sequential level numbers
                $currentGrowthMin += random_int(1, 1000); // strictly increasing
                $levels[] = [
                    'level' => $levelNumber,
                    'growth_value_min' => $currentGrowthMin,
                ];
            }

            // Shuffle to verify the method sorts internally before validating
            shuffle($levels);

            // Should not throw
            $this->service->validateLevelConfigs($levels);
            $this->assertTrue(true); // explicit assertion to confirm no exception
        });
    }

    /**
     * Property 1 (negative - duplicate levels): For any list of level configs where
     * at least two entries share the same level number, validateLevelConfigs must
     * throw a BusinessException.
     */
    public function testDuplicateLevelNumbersShouldThrow(): void
    {
        $this->limitTo(100);

        $this->forAll(
            Generators::choose(2, 10), // need at least 2 levels to create a duplicate
        )->then(function (int $count) {
            // Build valid levels first
            $levels = [];
            $currentGrowthMin = 0;

            for ($i = 0; $i < $count; ++$i) {
                $currentGrowthMin += random_int(1, 1000);
                $levels[] = [
                    'level' => $i + 1,
                    'growth_value_min' => $currentGrowthMin,
                ];
            }

            // Introduce a duplicate: pick a random index and copy its level number to another
            $srcIdx = random_int(0, $count - 1);
            $dstIdx = $srcIdx;
            while ($dstIdx === $srcIdx) {
                $dstIdx = random_int(0, $count - 1);
            }
            $levels[$dstIdx]['level'] = $levels[$srcIdx]['level'];

            $thrown = false;
            try {
                $this->service->validateLevelConfigs($levels);
            } catch (BusinessException) {
                $thrown = true;
            }

            $this->assertTrue($thrown, 'Expected BusinessException for duplicate level numbers');
        });
    }

    /**
     * Property 1 (negative - non-increasing thresholds): For any list of level configs
     * with unique level numbers but where growth_value_min is NOT strictly increasing
     * (when sorted by level), validateLevelConfigs must throw a BusinessException.
     */
    public function testNonIncreasingGrowthThresholdsShouldThrow(): void
    {
        $this->limitTo(100);

        $this->forAll(
            Generators::choose(2, 10), // need at least 2 levels
        )->then(function (int $count) {
            // Build levels with unique level numbers
            $levels = [];
            $currentGrowthMin = random_int(100, 10000);

            for ($i = 0; $i < $count; ++$i) {
                $levels[] = [
                    'level' => $i + 1,
                    'growth_value_min' => $currentGrowthMin,
                ];
                $currentGrowthMin += random_int(1, 1000);
            }

            // Now break the strictly increasing property:
            // Pick two adjacent levels (by level number) and make the higher one's
            // growth_value_min <= the lower one's
            $breakIdx = random_int(1, $count - 1);
            // Set growth_value_min to be <= the previous level's value
            $levels[$breakIdx]['growth_value_min'] = $levels[$breakIdx - 1]['growth_value_min'] - random_int(0, 100);

            $thrown = false;
            try {
                $this->service->validateLevelConfigs($levels);
            } catch (BusinessException) {
                $thrown = true;
            }

            $this->assertTrue($thrown, sprintf(
                'Expected BusinessException for non-increasing thresholds at level %d (value %d) <= level %d (value %d)',
                $levels[$breakIdx]['level'],
                $levels[$breakIdx]['growth_value_min'],
                $levels[$breakIdx - 1]['level'],
                $levels[$breakIdx - 1]['growth_value_min'],
            ));
        });
    }

    /**
     * Edge case: empty config list should pass validation without throwing.
     */
    public function testEmptyConfigListShouldPass(): void
    {
        $this->service->validateLevelConfigs([]);
        $this->assertTrue(true);
    }

    /**
     * Edge case: single level config should always pass validation.
     */
    public function testSingleLevelConfigShouldAlwaysPass(): void
    {
        $this->limitTo(100);

        $this->forAll(
            Generators::choose(1, 100),    // level number
            Generators::choose(0, 100000), // growth_value_min
        )->then(function (int $levelNumber, int $growthMin) {
            $levels = [
                ['level' => $levelNumber, 'growth_value_min' => $growthMin],
            ];

            $this->service->validateLevelConfigs($levels);
            $this->assertTrue(true);
        });
    }
}
