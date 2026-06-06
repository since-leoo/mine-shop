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

namespace App\Domain\Content\DiyPage\Service;

use App\Domain\Content\DiyPage\Contract\DiyPageDraftInput;
use App\Domain\Content\DiyPage\Contract\DiyPageInput;
use App\Domain\Content\DiyPage\Enum\DiyPageStatus;
use App\Domain\Content\DiyPage\Repository\DiyPageRepository;
use App\Domain\Content\DiyPage\ValueObject\DiyPageSchemaVo;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Content\DiyPage;
use App\Infrastructure\Model\Content\DiyPageVersion;

final class DomainDiyPageService extends IService
{
    public function __construct(public readonly DiyPageRepository $repository) {}

    public function page(array $params, int $page = 1, int $pageSize = 10): array
    {
        return $this->repository->page($params, $page, $pageSize);
    }

    public function create(DiyPageInput $input, ?int $operatorId = null): DiyPage
    {
        $this->assertPageType($input->getPageType());

        return $this->repository->createPage([
            'page_key' => trim($input->getPageKey()),
            'title' => trim($input->getTitle()),
            'page_type' => $input->getPageType(),
            'description' => $input->getDescription(),
            'is_enabled' => false,
            'status' => DiyPageStatus::PAGE_DISABLED,
            'published_version_id' => null,
            'created_by' => $operatorId,
            'updated_by' => $operatorId,
        ]);
    }

    public function update(int $id, DiyPageInput $input, ?int $operatorId = null): bool
    {
        $this->assertPageType($input->getPageType());

        return $this->repository->updatePage($id, [
            'page_key' => trim($input->getPageKey()),
            'title' => trim($input->getTitle()),
            'page_type' => $input->getPageType(),
            'description' => $input->getDescription(),
            'updated_by' => $operatorId,
        ]);
    }

    public function copy(int $id, ?int $operatorId = null): DiyPage
    {
        $source = $this->requirePage($id);

        return $this->repository->copyPage($source, [
            'title' => $source->title . '副本',
            'is_enabled' => false,
            'status' => DiyPageStatus::PAGE_DISABLED,
            'published_version_id' => null,
            'created_by' => $operatorId,
            'updated_by' => $operatorId,
        ]);
    }

    public function saveDraft(int $id, DiyPageDraftInput $input, ?int $operatorId = null): DiyPageVersion
    {
        $page = $this->requirePage($id);
        $schema = DiyPageSchemaVo::fromArray($input->getSchema(), $page->page_key)->toArray();

        return $this->repository->storeDraft($id, $schema, $operatorId);
    }

    public function publish(int $id, ?int $operatorId = null): DiyPageVersion
    {
        $page = $this->requirePage($id);
        $draft = $this->repository->findDraftVersion($id);

        if (! $draft instanceof DiyPageVersion) {
            throw new \DomainException('请先保存草稿后再发布');
        }

        DiyPageSchemaVo::fromArray($draft->schema, $page->page_key);

        return $this->repository->publishVersion($page, $draft, $operatorId);
    }

    public function enable(int $id, ?int $operatorId = null): bool
    {
        $page = $this->requirePage($id);
        if (empty($page->published_version_id)) {
            throw new \DomainException('请先发布页面后再启用');
        }

        $this->repository->disableSiblings($page->page_key, $page->page_type, $id);

        return $this->repository->updatePage($id, [
            'is_enabled' => true,
            'status' => DiyPageStatus::PAGE_PUBLISHED,
            'updated_by' => $operatorId,
        ]);
    }

    public function disable(int $id, ?int $operatorId = null): bool
    {
        return $this->repository->updatePage($id, [
            'is_enabled' => false,
            'status' => DiyPageStatus::PAGE_DISABLED,
            'updated_by' => $operatorId,
        ]);
    }

    public function getPublished(string $pageKey, string $pageType = DiyPageStatus::TYPE_ALL): ?array
    {
        $this->assertPageType($pageType);
        $version = $this->repository->findPublishedByPageKey($pageKey, $pageType);

        if (! $version instanceof DiyPageVersion && $pageType !== DiyPageStatus::TYPE_ALL) {
            $version = $this->repository->findPublishedByPageKey($pageKey, DiyPageStatus::TYPE_ALL);
        }

        if (! $version instanceof DiyPageVersion) {
            return null;
        }

        return DiyPageSchemaVo::fromArray($version->schema, $pageKey)->publishedPayload();
    }

    public function resetDraft(int $id, ?int $operatorId = null): DiyPageVersion
    {
        $page = $this->requirePage($id);
        $schema = $this->defaultSchema($page);

        return $this->repository->storeDraft($id, $schema, $operatorId);
    }

    private function requirePage(int $id): DiyPage
    {
        $page = $this->repository->findById($id);
        if (! $page instanceof DiyPage) {
            throw new \DomainException('DIY页面不存在');
        }

        return $page;
    }

    private function assertPageType(string $pageType): void
    {
        if (! \in_array($pageType, DiyPageStatus::pageTypes(), true)) {
            throw new \DomainException('页面类型无效');
        }
    }

    private function defaultSchema(DiyPage $page): array
    {
        return DiyPageSchemaVo::fromArray([
            'version' => 1,
            'page' => [
                'key' => $page->page_key,
                'title' => $page->title,
            ],
            'components' => [
                [
                    'id' => 'title-default',
                    'type' => 'title-bar',
                    'name' => '标题栏',
                    'enabled' => true,
                    'props' => [
                        'title' => $page->title,
                        'subtitle' => '',
                    ],
                    'style' => [],
                    'data' => [],
                ],
            ],
        ], $page->page_key)->toArray();
    }
}
