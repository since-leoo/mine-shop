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

use App\Domain\Content\DiyPage\Contract\DiyPageDraftInput;
use App\Domain\Content\DiyPage\Contract\DiyPageInput;
use App\Domain\Content\DiyPage\Enum\DiyPageStatus;
use App\Domain\Content\DiyPage\Repository\DiyPageRepository;
use App\Domain\Content\DiyPage\Service\DomainDiyPageService;
use App\Infrastructure\Model\Content\DiyPage;
use App\Infrastructure\Model\Content\DiyPageVersion;
use DG\BypassFinals;
use DomainException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class DomainDiyPageServiceTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        BypassFinals::enable();
    }

    public function testPageDelegatesFiltersToRepository(): void
    {
        $repository = new FakeDiyPageRepository();
        $repository->pageResult = ['list' => [], 'total' => 0];
        $service = new DomainDiyPageService($repository);

        $result = $service->page([
            'title' => '首页',
            'page_key' => 'home',
            'page_type' => DiyPageStatus::TYPE_MINIPROGRAM,
            'is_enabled' => true,
        ], 2, 15);

        self::assertSame(['list' => [], 'total' => 0], $result);
        self::assertSame(2, $repository->lastPage);
        self::assertSame(15, $repository->lastPageSize);
        self::assertSame('home', $repository->lastPageParams['page_key']);
        self::assertTrue($repository->lastPageParams['is_enabled']);
    }

    public function testCreatePersistsDisabledPageByDefault(): void
    {
        $repository = new FakeDiyPageRepository();
        $repository->createPageResult = $this->makePage(id: 11);
        $service = new DomainDiyPageService($repository);

        $page = $service->create($this->makePageInput(), 9001);

        self::assertSame(11, $page->id);
        self::assertSame('home', $repository->lastCreateData['page_key']);
        self::assertSame(DiyPageStatus::TYPE_MINIPROGRAM, $repository->lastCreateData['page_type']);
        self::assertFalse($repository->lastCreateData['is_enabled']);
        self::assertSame(DiyPageStatus::PAGE_DISABLED, $repository->lastCreateData['status']);
        self::assertSame(9001, $repository->lastCreateData['created_by']);
    }

    public function testUpdatePersistsEditablePageFields(): void
    {
        $repository = new FakeDiyPageRepository();
        $repository->updateResult = true;
        $service = new DomainDiyPageService($repository);

        self::assertTrue($service->update(12, $this->makePageInput(title: '新版首页'), 9002));

        self::assertSame(12, $repository->lastUpdateId);
        self::assertSame('新版首页', $repository->lastUpdateData['title']);
        self::assertSame(DiyPageStatus::TYPE_MINIPROGRAM, $repository->lastUpdateData['page_type']);
        self::assertSame(9002, $repository->lastUpdateData['updated_by']);
        self::assertArrayNotHasKey('is_enabled', $repository->lastUpdateData);
    }

    public function testCopyCreatesDisabledPageFromSource(): void
    {
        $source = $this->makePage(id: 21, title: '首页');
        $repository = new FakeDiyPageRepository();
        $repository->findByIdResult = $source;
        $repository->copyPageResult = $this->makePage(id: 22, title: '首页副本');
        $service = new DomainDiyPageService($repository);

        $copy = $service->copy(21, 9003);

        self::assertSame(22, $copy->id);
        self::assertSame($source, $repository->lastCopySource);
        self::assertSame('首页副本', $repository->lastCopyOverrides['title']);
        self::assertFalse($repository->lastCopyOverrides['is_enabled']);
        self::assertSame(DiyPageStatus::PAGE_DISABLED, $repository->lastCopyOverrides['status']);
        self::assertSame(9003, $repository->lastCopyOverrides['created_by']);
    }

    public function testSaveDraftValidatesAndStoresSchema(): void
    {
        $repository = new FakeDiyPageRepository();
        $repository->findByIdResult = $this->makePage(id: 31);
        $repository->storeDraftResult = $this->makeVersion(id: 301, pageId: 31, status: DiyPageStatus::VERSION_DRAFT);
        $service = new DomainDiyPageService($repository);

        $version = $service->saveDraft(31, $this->makeDraftInput($this->makeSchema()), 9004);

        self::assertSame(301, $version->id);
        self::assertSame(31, $repository->lastStoreDraftPageId);
        self::assertSame('banner-1', $repository->lastStoreDraftSchema['components'][0]['id']);
        self::assertTrue($repository->lastStoreDraftSchema['components'][0]['enabled']);
        self::assertSame(9004, $repository->lastStoreDraftOperatorId);
    }

    public function testPublishPromotesDraftVersion(): void
    {
        $page = $this->makePage(id: 41);
        $draft = $this->makeVersion(id: 401, pageId: 41, status: DiyPageStatus::VERSION_DRAFT);
        $published = $this->makeVersion(id: 401, pageId: 41, status: DiyPageStatus::VERSION_PUBLISHED);
        $repository = new FakeDiyPageRepository();
        $repository->findByIdResult = $page;
        $repository->findDraftVersionResult = $draft;
        $repository->publishVersionResult = $published;
        $service = new DomainDiyPageService($repository);

        $result = $service->publish(41, 9005);

        self::assertSame(DiyPageStatus::VERSION_PUBLISHED, $result->status);
        self::assertSame($page, $repository->lastPublishPage);
        self::assertSame($draft, $repository->lastPublishVersion);
        self::assertSame(9005, $repository->lastPublishOperatorId);
    }

    public function testEnableRequiresPublishedVersion(): void
    {
        $repository = new FakeDiyPageRepository();
        $repository->findByIdResult = $this->makePage(id: 51, publishedVersionId: null);
        $service = new DomainDiyPageService($repository);

        $this->expectException(DomainException::class);
        $service->enable(51, 9006);
    }

    public function testEnableDisablesSiblingsAndOpensCurrentPage(): void
    {
        $repository = new FakeDiyPageRepository();
        $repository->findByIdResult = $this->makePage(id: 52, publishedVersionId: 520);
        $repository->updateResult = true;
        $service = new DomainDiyPageService($repository);

        $service->enable(52, 9007);

        self::assertSame('home', $repository->lastDisableSiblingsPageKey);
        self::assertSame(DiyPageStatus::TYPE_MINIPROGRAM, $repository->lastDisableSiblingsPageType);
        self::assertSame(52, $repository->lastDisableSiblingsExceptId);
        self::assertSame(52, $repository->lastUpdateId);
        self::assertTrue($repository->lastUpdateData['is_enabled']);
        self::assertSame(DiyPageStatus::PAGE_PUBLISHED, $repository->lastUpdateData['status']);
        self::assertSame(9007, $repository->lastUpdateData['updated_by']);
    }

    public function testDisableClosesCurrentPageOnly(): void
    {
        $repository = new FakeDiyPageRepository();
        $repository->updateResult = true;
        $service = new DomainDiyPageService($repository);

        self::assertTrue($service->disable(61, 9008));

        self::assertSame(61, $repository->lastUpdateId);
        self::assertFalse($repository->lastUpdateData['is_enabled']);
        self::assertSame(DiyPageStatus::PAGE_DISABLED, $repository->lastUpdateData['status']);
        self::assertSame(9008, $repository->lastUpdateData['updated_by']);
    }

    public function testGetPublishedFallsBackToAllAndRemovesDisabledComponents(): void
    {
        $repository = new FakeDiyPageRepository();
        $repository->publishedByType = [
            'home|all' => $this->makeVersion(
                id: 701,
                pageId: 70,
                status: DiyPageStatus::VERSION_PUBLISHED,
                schema: $this->makeSchemaWithDisabledComponent()
            ),
        ];
        $service = new DomainDiyPageService($repository);

        $payload = $service->getPublished('home', DiyPageStatus::TYPE_MINIPROGRAM);

        self::assertIsArray($payload);
        self::assertSame('home', $payload['page']['key']);
        self::assertCount(1, $payload['components']);
        self::assertSame('banner-1', $payload['components'][0]['id']);
        self::assertSame([
            ['home', DiyPageStatus::TYPE_MINIPROGRAM],
            ['home', DiyPageStatus::TYPE_ALL],
        ], $repository->publishedLookups);
    }

    public function testResetDraftStoresDefaultHomeSchema(): void
    {
        $repository = new FakeDiyPageRepository();
        $repository->findByIdResult = $this->makePage(id: 81);
        $repository->storeDraftResult = $this->makeVersion(id: 801, pageId: 81, status: DiyPageStatus::VERSION_DRAFT);
        $service = new DomainDiyPageService($repository);

        $version = $service->resetDraft(81, 9009);

        self::assertSame(801, $version->id);
        self::assertSame('home', $repository->lastStoreDraftSchema['page']['key']);
        self::assertGreaterThanOrEqual(1, \count($repository->lastStoreDraftSchema['components']));
    }

    private function makePageInput(
        string $pageKey = 'home',
        string $pageType = DiyPageStatus::TYPE_MINIPROGRAM,
        string $title = '首页',
        ?string $description = '小程序首页'
    ): DiyPageInput {
        return new class($pageKey, $pageType, $title, $description) implements DiyPageInput {
            public function __construct(
                private readonly string $pageKey,
                private readonly string $pageType,
                private readonly string $title,
                private readonly ?string $description,
            ) {}

            public function getPageKey(): string
            {
                return $this->pageKey;
            }

            public function getPageType(): string
            {
                return $this->pageType;
            }

            public function getTitle(): string
            {
                return $this->title;
            }

            public function getDescription(): ?string
            {
                return $this->description;
            }
        };
    }

    private function makeDraftInput(array $schema): DiyPageDraftInput
    {
        return new class($schema) implements DiyPageDraftInput {
            public function __construct(private readonly array $schema) {}

            public function getSchema(): array
            {
                return $this->schema;
            }
        };
    }

    private function makePage(int $id, string $title = '首页', ?int $publishedVersionId = 1): DiyPage
    {
        $page = new class extends DiyPage {
            public function __construct() {}
        };
        $page->id = $id;
        $page->page_key = 'home';
        $page->title = $title;
        $page->page_type = DiyPageStatus::TYPE_MINIPROGRAM;
        $page->description = '小程序首页';
        $page->is_enabled = false;
        $page->status = DiyPageStatus::PAGE_DISABLED;
        $page->published_version_id = $publishedVersionId;

        return $page;
    }

    private function makeVersion(
        int $id,
        int $pageId,
        string $status,
        ?array $schema = null
    ): DiyPageVersion {
        $version = new class extends DiyPageVersion {
            public function __construct() {}
        };
        $version->id = $id;
        $version->page_id = $pageId;
        $version->version_no = 1;
        $version->status = $status;
        $version->schema = $schema ?? $this->makeSchema();

        return $version;
    }

    private function makeSchema(): array
    {
        return [
            'version' => 1,
            'page' => [
                'key' => 'home',
                'title' => '首页',
            ],
            'components' => [
                [
                    'id' => 'banner-1',
                    'type' => 'banner',
                    'name' => '轮播图',
                    'enabled' => true,
                    'props' => [],
                    'style' => [],
                    'data' => [
                        'items' => [],
                    ],
                ],
            ],
        ];
    }

    private function makeSchemaWithDisabledComponent(): array
    {
        $schema = $this->makeSchema();
        $schema['components'][] = [
            'id' => 'gap-1',
            'type' => 'gap',
            'name' => '辅助空白',
            'enabled' => false,
            'props' => ['height' => 16],
            'style' => [],
            'data' => [],
        ];

        return $schema;
    }
}

final class FakeDiyPageRepository extends DiyPageRepository
{
    public array $pageResult = [];

    public array $lastPageParams = [];

    public ?int $lastPage = null;

    public ?int $lastPageSize = null;

    public ?DiyPage $createPageResult = null;

    public array $lastCreateData = [];

    public bool $updateResult = false;

    public ?int $lastUpdateId = null;

    public array $lastUpdateData = [];

    public ?DiyPage $findByIdResult = null;

    public ?DiyPage $copyPageResult = null;

    public ?DiyPage $lastCopySource = null;

    public array $lastCopyOverrides = [];

    public ?int $lastStoreDraftPageId = null;

    public array $lastStoreDraftSchema = [];

    public ?int $lastStoreDraftOperatorId = null;

    public ?DiyPageVersion $storeDraftResult = null;

    public ?DiyPageVersion $findDraftVersionResult = null;

    public ?DiyPage $lastPublishPage = null;

    public ?DiyPageVersion $lastPublishVersion = null;

    public ?int $lastPublishOperatorId = null;

    public ?DiyPageVersion $publishVersionResult = null;

    public ?string $lastDisableSiblingsPageKey = null;

    public ?string $lastDisableSiblingsPageType = null;

    public ?int $lastDisableSiblingsExceptId = null;

    /** @var array<string, DiyPageVersion> */
    public array $publishedByType = [];

    /** @var array<int, array{0: string, 1: string}> */
    public array $publishedLookups = [];

    public function __construct() {}

    public function page(array $params = [], ?int $page = null, ?int $pageSize = null): array
    {
        $this->lastPageParams = $params;
        $this->lastPage = $page;
        $this->lastPageSize = $pageSize;

        return $this->pageResult;
    }

    public function createPage(array $data): DiyPage
    {
        $this->lastCreateData = $data;

        return $this->createPageResult ?? new DiyPage();
    }

    public function updatePage(int $id, array $data): bool
    {
        $this->lastUpdateId = $id;
        $this->lastUpdateData = $data;

        return $this->updateResult;
    }

    public function findById(int $id): ?object
    {
        return $this->findByIdResult;
    }

    public function copyPage(DiyPage $source, array $overrides): DiyPage
    {
        $this->lastCopySource = $source;
        $this->lastCopyOverrides = $overrides;

        return $this->copyPageResult ?? new DiyPage();
    }

    public function storeDraft(int $pageId, array $schema, ?int $operatorId): DiyPageVersion
    {
        $this->lastStoreDraftPageId = $pageId;
        $this->lastStoreDraftSchema = $schema;
        $this->lastStoreDraftOperatorId = $operatorId;

        return $this->storeDraftResult ?? new DiyPageVersion();
    }

    public function findDraftVersion(int $pageId): ?DiyPageVersion
    {
        return $this->findDraftVersionResult;
    }

    public function publishVersion(DiyPage $page, DiyPageVersion $version, ?int $operatorId): DiyPageVersion
    {
        $this->lastPublishPage = $page;
        $this->lastPublishVersion = $version;
        $this->lastPublishOperatorId = $operatorId;

        return $this->publishVersionResult ?? $version;
    }

    public function disableSiblings(string $pageKey, string $pageType, int $exceptId): void
    {
        $this->lastDisableSiblingsPageKey = $pageKey;
        $this->lastDisableSiblingsPageType = $pageType;
        $this->lastDisableSiblingsExceptId = $exceptId;
    }

    public function findPublishedByPageKey(string $pageKey, string $pageType = DiyPageStatus::TYPE_ALL): ?DiyPageVersion
    {
        $this->publishedLookups[] = [$pageKey, $pageType];

        return $this->publishedByType[$pageKey . '|' . $pageType] ?? null;
    }
}
