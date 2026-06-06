<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Member\Service;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\MemberSetting;
use App\Domain\Member\Repository\MemberLevelRepository;
use App\Domain\Member\Service\DomainMemberLevelService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Model\Member\MemberLevel;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class DomainMemberLevelServiceImportVipLevelsTest extends TestCase
{
    public function testImportVipLevelsCreatesAndUpdatesBySameNameWithoutTouchingUnmatchedRecords(): void
    {
        $existingGold = $this->makeLevelModel([
            'id' => 9,
            'name' => 'Gold',
            'level' => 7,
            'growth_value_min' => 700,
            'status' => 'inactive',
        ]);
        $existingManual = $this->makeLevelModel([
            'id' => 10,
            'name' => 'Manual',
            'level' => 99,
            'growth_value_min' => 9900,
            'status' => 'active',
        ]);

        $query = new class([$existingGold, $existingManual]) {
            /** @param MemberLevel[] $levels */
            public function __construct(private array $levels) {}

            public ?string $whereName = null;

            /** @var array<int, array<string, mixed>> */
            public array $created = [];

            public function where(string $column, mixed $value): self
            {
                if ($column === 'name') {
                    $this->whereName = (string) $value;
                }

                return $this;
            }

            public function first(): ?MemberLevel
            {
                foreach ($this->levels as $level) {
                    if ($level->name === $this->whereName) {
                        return $level;
                    }
                }

                return null;
            }

            public function create(array $payload): MemberLevel
            {
                $this->created[] = $payload;
                return new class($payload) extends MemberLevel {
                    public function __construct(private array $fakeAttributes) {}

                    public function getAttribute($key): mixed
                    {
                        return $this->fakeAttributes[$key] ?? null;
                    }
                };
            }
        };

        $repository = new MemberLevelRepository($this->makeModelReturningQuery($query));
        $service = new DomainMemberLevelService(
            $repository,
            $this->createMallSettingService([
                ['level' => 1, 'name' => 'Silver', 'growth' => 0],
                ['level' => 2, 'name' => 'Gold', 'growth_value_min' => 2000, 'icon' => '/gold.png'],
            ]),
        );

        $result = $service->importVipLevelsFromConfig(42);

        self::assertSame(['created' => 1, 'updated' => 1, 'skipped' => 0], $result);
        self::assertSame([
            'name' => 'Silver',
            'level' => 1,
            'growth_value_min' => 0,
            'status' => 'active',
            'sort_order' => 1,
            'created_by' => 42,
        ], $query->created[0]);
        self::assertSame([
            'name' => 'Gold',
            'level' => 2,
            'growth_value_min' => 2000,
            'status' => 'active',
            'sort_order' => 2,
            'icon' => '/gold.png',
            'updated_by' => 42,
        ], $existingGold->updatedPayload);
        self::assertNull($existingManual->updatedPayload);
    }

    public function testImportVipLevelsRejectsDuplicateNames(): void
    {
        $service = new DomainMemberLevelService(
            new MemberLevelRepository($this->makeModelReturningQuery(new class {
                public function where(string $column, mixed $value): self
                {
                    return $this;
                }

                public function first(): ?MemberLevel
                {
                    return null;
                }

                public function create(array $payload): MemberLevel
                {
                    return new class($payload) extends MemberLevel {
                        public function __construct(private array $fakeAttributes) {}

                        public function getAttribute($key): mixed
                        {
                            return $this->fakeAttributes[$key] ?? null;
                        }
                    };
                }
            })),
            $this->createMallSettingService([
                ['level' => 1, 'name' => 'Silver', 'growth' => 0],
                ['level' => 2, 'name' => 'Silver', 'growth' => 1000],
            ]),
        );

        $this->expectException(BusinessException::class);

        $service->importVipLevelsFromConfig();
    }

    public function testImportVipLevelsRejectsNonIncreasingGrowthValues(): void
    {
        $service = new DomainMemberLevelService(
            new MemberLevelRepository($this->makeModelReturningQuery(new class {
                public function where(string $column, mixed $value): self
                {
                    return $this;
                }

                public function first(): ?MemberLevel
                {
                    return null;
                }

                public function create(array $payload): MemberLevel
                {
                    return new class($payload) extends MemberLevel {
                        public function __construct(private array $fakeAttributes) {}

                        public function getAttribute($key): mixed
                        {
                            return $this->fakeAttributes[$key] ?? null;
                        }
                    };
                }
            })),
            $this->createMallSettingService([
                ['level' => 1, 'name' => 'Silver', 'growth' => 1000],
                ['level' => 2, 'name' => 'Gold', 'growth' => 1000],
            ]),
        );

        $this->expectException(BusinessException::class);

        $service->importVipLevelsFromConfig();
    }

    private function makeModelReturningQuery(object $query): MemberLevel
    {
        $model = $this->getMockBuilder(MemberLevel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['newQuery'])
            ->getMock();
        $model->method('newQuery')->willReturn($query);

        return $model;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function makeLevelModel(array $attributes): MemberLevel
    {
        return new class($attributes) extends MemberLevel {
            public ?array $updatedPayload = null;

            public function __construct(private array $fakeAttributes) {}

            public function getAttribute($key): mixed
            {
                return $this->fakeAttributes[$key] ?? null;
            }

            public function update(array $attributes = [], array $options = []): bool
            {
                $this->updatedPayload = $attributes;
                $this->fakeAttributes = array_merge($this->fakeAttributes, $attributes);

                return true;
            }
        };
    }

    /**
     * @param array<int, array<string, mixed>> $vipLevels
     */
    private function createMallSettingService(array $vipLevels): DomainMallSettingService
    {
        $reflection = new \ReflectionClass(DomainMallSettingService::class);
        $service = $reflection->newInstanceWithoutConstructor();

        $memberProperty = $reflection->getProperty('member');
        $memberProperty->setAccessible(true);
        $memberProperty->setValue($service, new MemberSetting(true, 0, 0, 0, 0, $vipLevels, 1, 0));

        return $service;
    }
}
