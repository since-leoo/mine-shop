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
use Hyperf\Collection\Collection;
use PHPUnit\Framework\TestCase;

/**
 * Feature: member-vip-level, Property 2: 等级列表查询排序.
 *
 * Validates: Requirements 1.6
 *
 * For any set of levels stored in the database, calling getActiveLevels()
 * must return results sorted by level number (level) in ascending order.
 * @internal
 * @coversNothing
 */
final class DomainMemberLevelServiceSortTest extends TestCase
{
    use TestTrait;

    /**
     * Property 2 (core): For any set of active levels stored in random order,
     * getActiveLevels() must return them sorted by level number ascending.
     */
    public function testGetActiveLevelsReturnsSortedByLevelAscending(): void
    {
        $this->limitTo(200);

        $this->forAll(
            Generators::choose(1, 15),
        )->then(function (int $count) {
            $levelData = $this->generateShuffledLevels($count);
            $service = $this->buildServiceWithLevels($levelData);

            $result = $service->getActiveLevels();

            $this->assertCount($count, $result);

            for ($i = 1; $i < \count($result); ++$i) {
                $prevLevel = $result[$i - 1]->level;
                $currLevel = $result[$i]->level;

                $this->assertLessThan(
                    $currLevel,
                    $prevLevel,
                    \sprintf(
                        'Levels not in ascending order: position %d has level %d, position %d has level %d',
                        $i - 1,
                        $prevLevel,
                        $i,
                        $currLevel,
                    ),
                );
            }
        });
    }

    /**
     * Property 2 (completeness): getActiveLevels() must return all levels
     * that were stored, with no missing or extra entries.
     */
    public function testGetActiveLevelsReturnsAllStoredLevels(): void
    {
        $this->limitTo(200);

        $this->forAll(
            Generators::choose(1, 15),
        )->then(function (int $count) {
            $levelData = $this->generateShuffledLevels($count);
            $service = $this->buildServiceWithLevels($levelData);

            $result = $service->getActiveLevels();

            $resultLevels = array_map(static fn ($l) => $l->level, $result);
            sort($resultLevels);

            $expectedLevels = range(1, $count);

            $this->assertSame(
                $expectedLevels,
                $resultLevels,
                \sprintf(
                    'Expected levels %s but got %s',
                    implode(',', $expectedLevels),
                    implode(',', $resultLevels),
                ),
            );
        });
    }

    /**
     * Edge case: empty level set should return empty array.
     */
    public function testGetActiveLevelsWithEmptySetReturnsEmptyArray(): void
    {
        $service = $this->buildServiceWithLevels([]);
        $result = $service->getActiveLevels();

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    /**
     * Edge case: single level should return array with that one level.
     */
    public function testGetActiveLevelsWithSingleLevelReturnsThatLevel(): void
    {
        $this->limitTo(100);

        $this->forAll(
            Generators::choose(1, 100),
            Generators::choose(0, 100000),
        )->then(function (int $levelNumber, int $growthMin) {
            $levelData = [[
                'level' => $levelNumber,
                'growth_value_min' => $growthMin,
                'status' => 'active',
                'name' => 'VIP' . $levelNumber,
            ]];

            $service = $this->buildServiceWithLevels($levelData);
            $result = $service->getActiveLevels();

            $this->assertCount(1, $result);
            $this->assertSame($levelNumber, $result[0]->level);
        });
    }

    /**
     * Build a DomainMemberLevelService whose getActiveLevels query chain
     * returns levels from the given data (in random order), with orderBy
     * triggering the sort.
     *
     * @param array<int, array{level: int, growth_value_min: int, status: string, name: string}> $levelData
     */
    private function buildServiceWithLevels(array $levelData): DomainMemberLevelService
    {
        $testCase = $this;

        // Fake query builder that simulates where/orderBy/get chain
        $fakeBuilder = new class($levelData, $testCase) {
            private array $levels;

            private ?string $orderByColumn = null;

            private string $orderByDirection = 'asc';

            private TestCase $testCase;

            public function __construct(array $levels, TestCase $testCase)
            {
                $this->levels = $levels;
                $this->testCase = $testCase;
            }

            public function where(...$args): self
            {
                return $this;
            }

            public function orderBy(string $column, string $direction = 'asc'): self
            {
                $this->orderByColumn = $column;
                $this->orderByDirection = $direction;
                return $this;
            }

            public function get(): Collection
            {
                $sorted = $this->levels;

                if ($this->orderByColumn !== null) {
                    usort($sorted, function (array $a, array $b) {
                        $cmp = $a[$this->orderByColumn] <=> $b[$this->orderByColumn];
                        return $this->orderByDirection === 'desc' ? -$cmp : $cmp;
                    });
                }

                $mocks = array_map(function (array $data) {
                    $mock = $this->testCase->getMockBuilder(MemberLevel::class)
                        ->disableOriginalConstructor()
                        ->onlyMethods(['getAttribute'])
                        ->getMock();

                    $mock->method('getAttribute')
                        ->willReturnCallback(static fn (string $key) => $data[$key] ?? null);

                    return $mock;
                }, $sorted);

                return new Collection($mocks);
            }
        };

        // Create a MemberLevel mock for newQuery()
        $model = $this->getMockBuilder(MemberLevel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['newQuery'])
            ->getMock();
        $model->method('newQuery')->willReturn($fakeBuilder);

        // Use reflection to bypass final classes
        $repository = (new \ReflectionClass(MemberLevelRepository::class))
            ->newInstanceWithoutConstructor();
        $refProp = new \ReflectionProperty(MemberLevelRepository::class, 'model');
        $refProp->setValue($repository, $model);

        $mallSettingService = (new \ReflectionClass(DomainMallSettingService::class))
            ->newInstanceWithoutConstructor();

        return new DomainMemberLevelService($repository, $mallSettingService);
    }

    /**
     * Generate a list of level data with unique level numbers and random growth_value_min,
     * then shuffle to simulate random database storage order.
     */
    private function generateShuffledLevels(int $count): array
    {
        $levels = [];
        $currentMin = 0;

        for ($i = 0; $i < $count; ++$i) {
            $levels[] = [
                'level' => $i + 1,
                'growth_value_min' => $currentMin,
                'status' => 'active',
                'name' => 'VIP' . ($i + 1),
            ];
            $currentMin += random_int(1, 5000);
        }

        shuffle($levels);

        return $levels;
    }
}
