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

namespace HyperfTests\Unit\Domain\Content\DiyPage\Service;

use App\Domain\Content\DiyPage\Contract\DiyPublishScheduleInput;
use App\Domain\Content\DiyPage\Enum\DiyPageStatus;
use App\Domain\Content\DiyPage\Repository\DiyPageRepository;
use App\Domain\Content\DiyPage\Repository\DiyPublishRecordRepository;
use App\Domain\Content\DiyPage\Service\DomainDiyPageService;
use App\Domain\Content\DiyPage\Service\DomainDiyPublishService;
use App\Infrastructure\Model\Content\DiyPage;
use App\Infrastructure\Model\Content\DiyPagePreviewToken;
use App\Infrastructure\Model\Content\DiyPagePublishRecord;
use App\Infrastructure\Model\Content\DiyPageVersion;
use Carbon\Carbon;
use DG\BypassFinals;
use DomainException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class DomainDiyPublishServiceTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        BypassFinals::enable();
    }

    public function testScheduleRejectsPastTime(): void
    {
        $service = new DomainDiyPublishService(new FakeDiyPublishRecordRepository(), $this->makePageService());

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('定时发布时间必须晚于当前时间');

        $service->schedule($this->makeScheduleInput(Carbon::now()->subMinute()), 9001);
    }

    public function testScheduleRejectsDuplicatePendingPlan(): void
    {
        $repository = new FakeDiyPublishRecordRepository();
        $repository->hasPendingScheduleResult = true;
        $service = new DomainDiyPublishService($repository, $this->makePageService());

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('已有待执行的定时发布');

        $service->schedule($this->makeScheduleInput(Carbon::now()->addHour()), 9001);
    }

    public function testScheduleCreatesPendingRecord(): void
    {
        $repository = new FakeDiyPublishRecordRepository();
        $repository->createRecordResult = $this->makeRecord(id: 11);
        $service = new DomainDiyPublishService($repository, $this->makePageService());
        $scheduledAt = Carbon::now()->addHour();

        $record = $service->schedule($this->makeScheduleInput($scheduledAt), 9002);

        self::assertSame(11, $record->id);
        self::assertSame(31, $repository->lastCreateRecordData['page_id']);
        self::assertSame(301, $repository->lastCreateRecordData['version_id']);
        self::assertSame('scheduled', $repository->lastCreateRecordData['publish_type']);
        self::assertSame('pending', $repository->lastCreateRecordData['publish_status']);
        self::assertSame(9002, $repository->lastCreateRecordData['operator_id']);
    }

    public function testCancelScheduleRequiresPendingRecord(): void
    {
        $repository = new FakeDiyPublishRecordRepository();
        $repository->findRecordResult = $this->makeRecord(id: 12, status: 'published');
        $service = new DomainDiyPublishService($repository, $this->makePageService());

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('只能取消待发布记录');

        $service->cancelSchedule(12);
    }

    public function testRollbackPublishesHistoricalVersionAndCreatesRecord(): void
    {
        $recordRepository = new FakeDiyPublishRecordRepository();
        $pageRepository = new FakePublishDiyPageRepository();
        $pageRepository->findByIdResult = $this->makePage(id: 41);
        $pageRepository->findVersionResult = $this->makeVersion(id: 401, pageId: 41, status: DiyPageStatus::VERSION_PUBLISHED);
        $pageRepository->publishVersionResult = $this->makeVersion(id: 401, pageId: 41, status: DiyPageStatus::VERSION_PUBLISHED);
        $service = new DomainDiyPublishService($recordRepository, new DomainDiyPageService($pageRepository));

        $service->rollback(41, 401, 9003);

        self::assertSame(41, $pageRepository->lastPublishPage?->id);
        self::assertSame(401, $pageRepository->lastPublishVersion?->id);
        self::assertSame('rollback', $recordRepository->lastCreateRecordData['publish_type']);
        self::assertSame('published', $recordRepository->lastCreateRecordData['publish_status']);
    }

    public function testCreatePreviewTokenStoresExpiry(): void
    {
        $repository = new FakeDiyPublishRecordRepository();
        $repository->createPreviewTokenResult = $this->makePreviewToken(id: 21);
        $service = new DomainDiyPublishService($repository, $this->makePageService());

        $token = $service->createPreviewToken(51, 501, 9004);

        self::assertSame(21, $token->id);
        self::assertSame(51, $repository->lastCreatePreviewTokenData['page_id']);
        self::assertSame(501, $repository->lastCreatePreviewTokenData['version_id']);
        self::assertSame(9004, $repository->lastCreatePreviewTokenData['created_by']);
        self::assertNotEmpty($repository->lastCreatePreviewTokenData['token']);
        self::assertTrue($repository->lastCreatePreviewTokenData['expired_at']->isFuture());
    }

    public function testResolvePreviewRejectsExpiredToken(): void
    {
        $repository = new FakeDiyPublishRecordRepository();
        $repository->findPreviewTokenResult = $this->makePreviewToken(id: 22, expiredAt: Carbon::now()->subMinute());
        $service = new DomainDiyPublishService($repository, $this->makePageService());

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('预览令牌已过期');

        $service->resolvePreview('expired');
    }

    private function makeScheduleInput(Carbon $scheduledAt): DiyPublishScheduleInput
    {
        return new class($scheduledAt) implements DiyPublishScheduleInput {
            public function __construct(private readonly Carbon $scheduledAt) {}

            public function getPageId(): int
            {
                return 31;
            }

            public function getVersionId(): int
            {
                return 301;
            }

            public function getScheduledAt(): Carbon
            {
                return $this->scheduledAt;
            }

            public function getRemark(): ?string
            {
                return '晚八点发布';
            }
        };
    }

    private function makePageService(): DomainDiyPageService
    {
        return new DomainDiyPageService(new FakePublishDiyPageRepository());
    }

    private function makePage(int $id): DiyPage
    {
        $page = new class extends DiyPage {
            public function __construct() {}
        };
        $page->id = $id;
        $page->page_key = 'home';
        $page->page_type = DiyPageStatus::TYPE_MINIPROGRAM;

        return $page;
    }

    private function makeVersion(int $id, int $pageId, string $status): DiyPageVersion
    {
        $version = new class extends DiyPageVersion {
            public function __construct() {}
        };
        $version->id = $id;
        $version->page_id = $pageId;
        $version->status = $status;
        $version->schema = [
            'version' => 1,
            'page' => ['key' => 'home', 'title' => '首页'],
            'components' => [],
        ];

        return $version;
    }

    private function makeRecord(int $id, string $status = 'pending'): DiyPagePublishRecord
    {
        $record = new class extends DiyPagePublishRecord {
            public function __construct() {}
        };
        $record->id = $id;
        $record->page_id = 31;
        $record->version_id = 301;
        $record->publish_status = $status;

        return $record;
    }

    private function makePreviewToken(int $id, ?Carbon $expiredAt = null): DiyPagePreviewToken
    {
        $token = new class($expiredAt ?? Carbon::now()->addMinutes(30)) extends DiyPagePreviewToken {
            public Carbon $rawExpiredAt;

            public function __construct(Carbon $expiredAt)
            {
                $this->rawExpiredAt = $expiredAt;
            }

            public function getAttribute($key)
            {
                if ($key === 'expired_at') {
                    return $this->rawExpiredAt;
                }

                return parent::getAttribute($key);
            }
        };
        $token->id = $id;
        $token->page_id = 51;
        $token->version_id = 501;
        $token->token = 'preview-token';

        return $token;
    }
}

final class FakeDiyPublishRecordRepository extends DiyPublishRecordRepository
{
    public bool $hasPendingScheduleResult = false;

    public ?DiyPagePublishRecord $createRecordResult = null;

    public array $lastCreateRecordData = [];

    public ?DiyPagePublishRecord $findRecordResult = null;

    public ?int $lastCancelRecordId = null;

    public ?DiyPagePreviewToken $createPreviewTokenResult = null;

    public array $lastCreatePreviewTokenData = [];

    public ?DiyPagePreviewToken $findPreviewTokenResult = null;

    public function __construct() {}

    public function hasPendingSchedule(int $pageId): bool
    {
        return $this->hasPendingScheduleResult;
    }

    public function createRecord(array $data): DiyPagePublishRecord
    {
        $this->lastCreateRecordData = $data;

        return $this->createRecordResult ?? new class extends DiyPagePublishRecord {
            public function __construct() {}
        };
    }

    public function findRecord(int $id): ?DiyPagePublishRecord
    {
        return $this->findRecordResult;
    }

    public function cancelRecord(int $id): bool
    {
        $this->lastCancelRecordId = $id;

        return true;
    }

    public function createPreviewToken(array $data): DiyPagePreviewToken
    {
        $this->lastCreatePreviewTokenData = $data;

        return $this->createPreviewTokenResult ?? new class($data['expired_at']) extends DiyPagePreviewToken {
            public Carbon $rawExpiredAt;

            public function __construct(Carbon $expiredAt)
            {
                $this->rawExpiredAt = $expiredAt;
            }

            public function getAttribute($key)
            {
                if ($key === 'expired_at') {
                    return $this->rawExpiredAt;
                }

                return parent::getAttribute($key);
            }
        };
    }

    public function findPreviewToken(string $token): ?DiyPagePreviewToken
    {
        return $this->findPreviewTokenResult;
    }
}

final class FakePublishDiyPageRepository extends DiyPageRepository
{
    public ?DiyPage $findByIdResult = null;

    public ?DiyPageVersion $findVersionResult = null;

    public ?DiyPage $lastPublishPage = null;

    public ?DiyPageVersion $lastPublishVersion = null;

    public ?DiyPageVersion $publishVersionResult = null;

    public function __construct() {}

    public function findById(int $id): ?object
    {
        return $this->findByIdResult;
    }

    public function findVersion(int $pageId, int $versionId): ?DiyPageVersion
    {
        return $this->findVersionResult;
    }

    public function publishVersion(DiyPage $page, DiyPageVersion $version, ?int $operatorId): DiyPageVersion
    {
        $this->lastPublishPage = $page;
        $this->lastPublishVersion = $version;

        return $this->publishVersionResult ?? $version;
    }
}
