<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Member\Service;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\MemberSetting;
use App\Domain\Member\Repository\MemberLevelRepository;
use App\Domain\Member\Service\DomainMemberLevelService;
use App\Infrastructure\Model\Member\MemberLevel;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\ModelNotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class DomainMemberLevelServiceFallbackTest extends TestCase
{
    public function testMatchLevelByGrowthValueReturnsNullWhenNoLevelConfigExists(): void
    {
        $builder = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['where', 'whereKey', 'first', 'firstOrFail', 'orderBy'])
            ->addMethods(['orderByDesc'])
            ->getMock();

        $builder->method('where')->willReturnSelf();
        $builder->method('whereKey')->willReturnSelf();
        $builder->method('orderByDesc')->willReturnSelf();
        $builder->method('orderBy')->willReturnSelf();
        $builder->method('first')->willReturn(null);
        $builder->method('firstOrFail')->willThrowException(new ModelNotFoundException());

        $model = $this->createMock(MemberLevel::class);
        $model->method('newQuery')->willReturn($builder);

        $repository = new MemberLevelRepository($model);
        $mallSettingService = $this->createMallSettingService(0);

        $service = new DomainMemberLevelService($repository, $mallSettingService);

        self::assertNull($service->matchLevelByGrowthValue(38800));
    }

    private function createMallSettingService(int $defaultLevelId): DomainMallSettingService
    {
        $reflection = new \ReflectionClass(DomainMallSettingService::class);
        $service = $reflection->newInstanceWithoutConstructor();

        $memberProperty = $reflection->getProperty('member');
        $memberProperty->setAccessible(true);
        $memberProperty->setValue($service, new MemberSetting(true, 0, 0, 0, 0, [], $defaultLevelId, 0));

        return $service;
    }
}
