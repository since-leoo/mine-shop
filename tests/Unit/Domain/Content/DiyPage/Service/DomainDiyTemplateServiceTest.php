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

use App\Domain\Content\DiyPage\Contract\DiyTemplateApplyInput;
use App\Domain\Content\DiyPage\Contract\DiyTemplateInput;
use App\Domain\Content\DiyPage\Enum\DiyPageStatus;
use App\Domain\Content\DiyPage\Repository\DiyPageRepository;
use App\Domain\Content\DiyPage\Repository\DiyTemplateRepository;
use App\Domain\Content\DiyPage\Service\DomainDiyPageService;
use App\Domain\Content\DiyPage\Service\DomainDiyTemplateService;
use App\Infrastructure\Model\Content\DiyPage;
use App\Infrastructure\Model\Content\DiyPageVersion;
use App\Infrastructure\Model\Content\DiyTemplate;
use App\Infrastructure\Model\Content\DiyTemplateCategory;
use DG\BypassFinals;
use DomainException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class DomainDiyTemplateServiceTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        BypassFinals::enable();
    }

    public function testCreateValidatesSchemaBeforePersisting(): void
    {
        $repository = new FakeDiyTemplateRepository();
        $repository->createTemplateResult = $this->makeTemplate(id: 11);
        $service = new DomainDiyTemplateService($repository, $this->makePageService());

        $template = $service->create($this->makeTemplateInput(), 9001);

        self::assertSame(11, $template->id);
        self::assertSame('首页模板', $repository->lastCreateData['name']);
        self::assertSame('home', $repository->lastCreateData['page_key']);
        self::assertSame(DiyPageStatus::TYPE_ALL, $repository->lastCreateData['page_type']);
        self::assertTrue($repository->lastCreateData['is_enabled']);
        self::assertSame(1, $repository->lastCreateData['schema']['version']);
    }

    public function testCreateRejectsInvalidSchema(): void
    {
        $repository = new FakeDiyTemplateRepository();
        $service = new DomainDiyTemplateService($repository, $this->makePageService());
        $schema = $this->makeSchema();
        $schema['components'][0]['type'] = 'unknown';

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('不支持的装修组件');

        $service->create($this->makeTemplateInput(schema: $schema), 9001);
    }

    public function testDisableTemplateCannotApply(): void
    {
        $repository = new FakeDiyTemplateRepository();
        $repository->findDetailResult = $this->makeTemplate(id: 12, isEnabled: false);
        $service = new DomainDiyTemplateService($repository, $this->makePageService());

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('模板已禁用');

        $service->apply($this->makeApplyInput(templateId: 12, pageId: 31), 9002);
    }

    public function testApplyTemplateStoresPageDraftOnly(): void
    {
        $templateRepository = new FakeDiyTemplateRepository();
        $templateRepository->findDetailResult = $this->makeTemplate(id: 13);
        $pageRepository = new FakeTemplateDiyPageRepository();
        $pageRepository->findByIdResult = $this->makePage(id: 32);
        $pageRepository->storeDraftResult = $this->makeVersion(id: 3201, pageId: 32);
        $pageService = new DomainDiyPageService($pageRepository);
        $service = new DomainDiyTemplateService($templateRepository, $pageService);

        $version = $service->apply($this->makeApplyInput(templateId: 13, pageId: 32), 9003);

        self::assertSame(3201, $version->id);
        self::assertSame(32, $pageRepository->lastStoreDraftPageId);
        self::assertSame(9003, $pageRepository->lastStoreDraftOperatorId);
        self::assertSame('home', $pageRepository->lastStoreDraftSchema['page']['key']);
        self::assertSame('banner-1', $pageRepository->lastStoreDraftSchema['components'][0]['id']);
    }

    public function testEnableAndDisableDelegateToRepository(): void
    {
        $repository = new FakeDiyTemplateRepository();
        $service = new DomainDiyTemplateService($repository, $this->makePageService());

        $service->enable(15);
        $service->disable(16);

        self::assertSame(15, $repository->lastEnableId);
        self::assertSame(16, $repository->lastDisableId);
    }

    private function makeTemplateInput(
        int $categoryId = 1,
        string $name = '首页模板',
        string $pageKey = 'home',
        string $pageType = DiyPageStatus::TYPE_ALL,
        ?array $schema = null,
        bool $isEnabled = true
    ): DiyTemplateInput {
        return new class($categoryId, $name, $pageKey, $pageType, $schema ?? $this->makeSchema(), $isEnabled) implements DiyTemplateInput {
            public function __construct(
                private readonly int $categoryId,
                private readonly string $name,
                private readonly string $pageKey,
                private readonly string $pageType,
                private readonly array $schema,
                private readonly bool $isEnabled,
            ) {}

            public function getCategoryId(): int
            {
                return $this->categoryId;
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function getPageKey(): string
            {
                return $this->pageKey;
            }

            public function getPageType(): string
            {
                return $this->pageType;
            }

            public function getCover(): ?string
            {
                return null;
            }

            public function getDescription(): ?string
            {
                return '默认首页模板';
            }

            public function getSchema(): array
            {
                return $this->schema;
            }

            public function getSort(): int
            {
                return 0;
            }

            public function isEnabled(): bool
            {
                return $this->isEnabled;
            }
        };
    }

    private function makeApplyInput(int $templateId, int $pageId): DiyTemplateApplyInput
    {
        return new class($templateId, $pageId) implements DiyTemplateApplyInput {
            public function __construct(
                private readonly int $templateId,
                private readonly int $pageId,
            ) {}

            public function getTemplateId(): int
            {
                return $this->templateId;
            }

            public function getPageId(): int
            {
                return $this->pageId;
            }
        };
    }

    private function makePageService(): DomainDiyPageService
    {
        return new DomainDiyPageService(new FakeTemplateDiyPageRepository());
    }

    private function makeTemplate(int $id, bool $isEnabled = true): DiyTemplate
    {
        $template = new class extends DiyTemplate {
            public function __construct() {}
        };
        $template->id = $id;
        $template->category_id = 1;
        $template->name = '首页模板';
        $template->page_key = 'home';
        $template->page_type = DiyPageStatus::TYPE_ALL;
        $template->schema = $this->makeSchema();
        $template->is_enabled = $isEnabled;

        return $template;
    }

    private function makePage(int $id): DiyPage
    {
        $page = new class extends DiyPage {
            public function __construct() {}
        };
        $page->id = $id;
        $page->page_key = 'home';
        $page->title = '首页';
        $page->page_type = DiyPageStatus::TYPE_MINIPROGRAM;
        $page->is_enabled = false;
        $page->status = DiyPageStatus::PAGE_DISABLED;

        return $page;
    }

    private function makeVersion(int $id, int $pageId): DiyPageVersion
    {
        $version = new class extends DiyPageVersion {
            public function __construct() {}
        };
        $version->id = $id;
        $version->page_id = $pageId;
        $version->status = DiyPageStatus::VERSION_DRAFT;
        $version->schema = $this->makeSchema();

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
                    'data' => ['items' => []],
                ],
            ],
        ];
    }
}

final class FakeDiyTemplateRepository extends DiyTemplateRepository
{
    public ?DiyTemplate $createTemplateResult = null;

    public array $lastCreateData = [];

    public ?int $lastUpdateId = null;

    public array $lastUpdateData = [];

    public ?DiyTemplate $findDetailResult = null;

    public ?int $lastEnableId = null;

    public ?int $lastDisableId = null;

    public function __construct() {}

    public function createTemplate(array $data): DiyTemplate
    {
        $this->lastCreateData = $data;

        return $this->createTemplateResult ?? new DiyTemplate();
    }

    public function updateTemplate(int $id, array $data): bool
    {
        $this->lastUpdateId = $id;
        $this->lastUpdateData = $data;

        return true;
    }

    public function findDetail(int $id): ?DiyTemplate
    {
        return $this->findDetailResult;
    }

    public function enable(int $id): bool
    {
        $this->lastEnableId = $id;

        return true;
    }

    public function disable(int $id): bool
    {
        $this->lastDisableId = $id;

        return true;
    }
}

final class FakeTemplateDiyPageRepository extends DiyPageRepository
{
    public ?DiyPage $findByIdResult = null;

    public ?DiyPageVersion $storeDraftResult = null;

    public ?int $lastStoreDraftPageId = null;

    public array $lastStoreDraftSchema = [];

    public ?int $lastStoreDraftOperatorId = null;

    public function __construct() {}

    public function findById(int $id): ?object
    {
        return $this->findByIdResult;
    }

    public function storeDraft(int $pageId, array $schema, ?int $operatorId): DiyPageVersion
    {
        $this->lastStoreDraftPageId = $pageId;
        $this->lastStoreDraftSchema = $schema;
        $this->lastStoreDraftOperatorId = $operatorId;

        return $this->storeDraftResult ?? new DiyPageVersion();
    }
}
