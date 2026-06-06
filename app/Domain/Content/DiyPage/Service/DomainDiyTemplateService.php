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
use App\Domain\Content\DiyPage\Contract\DiyTemplateApplyInput;
use App\Domain\Content\DiyPage\Contract\DiyTemplateInput;
use App\Domain\Content\DiyPage\Enum\DiyPageStatus;
use App\Domain\Content\DiyPage\Repository\DiyTemplateRepository;
use App\Domain\Content\DiyPage\ValueObject\DiyPageSchemaVo;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Content\DiyTemplate;
use App\Infrastructure\Model\Content\DiyPageVersion;

final class DomainDiyTemplateService extends IService
{
    public function __construct(
        public readonly DiyTemplateRepository $repository,
        private readonly DomainDiyPageService $pageService,
    ) {}

    public function page(array $params, int $page = 1, int $pageSize = 10): array
    {
        return $this->repository->page($params, $page, $pageSize);
    }

    public function create(DiyTemplateInput $input, ?int $operatorId = null): DiyTemplate
    {
        $this->assertPageType($input->getPageType());
        $schema = DiyPageSchemaVo::fromArray($input->getSchema(), $input->getPageKey())->toArray();

        return $this->repository->createTemplate([
            'category_id' => $input->getCategoryId(),
            'name' => trim($input->getName()),
            'page_key' => trim($input->getPageKey()),
            'page_type' => $input->getPageType(),
            'cover' => $input->getCover(),
            'description' => $input->getDescription(),
            'schema' => $schema,
            'sort' => $input->getSort(),
            'is_enabled' => $input->isEnabled(),
        ]);
    }

    public function update(int $id, DiyTemplateInput $input, ?int $operatorId = null): bool
    {
        $this->assertPageType($input->getPageType());
        $schema = DiyPageSchemaVo::fromArray($input->getSchema(), $input->getPageKey())->toArray();

        return $this->repository->updateTemplate($id, [
            'category_id' => $input->getCategoryId(),
            'name' => trim($input->getName()),
            'page_key' => trim($input->getPageKey()),
            'page_type' => $input->getPageType(),
            'cover' => $input->getCover(),
            'description' => $input->getDescription(),
            'schema' => $schema,
            'sort' => $input->getSort(),
            'is_enabled' => $input->isEnabled(),
        ]);
    }

    public function enable(int $id): bool
    {
        return $this->repository->enable($id);
    }

    public function disable(int $id): bool
    {
        return $this->repository->disable($id);
    }

    public function apply(DiyTemplateApplyInput $input, ?int $operatorId = null): DiyPageVersion
    {
        $template = $this->requireTemplate($input->getTemplateId());
        if (! $template->is_enabled) {
            throw new \DomainException('模板已禁用');
        }

        return $this->pageService->saveDraft(
            $input->getPageId(),
            new class(DiyPageSchemaVo::fromArray($template->schema, $template->page_key)->toArray()) implements DiyPageDraftInput {
                public function __construct(private readonly array $schema) {}

                public function getSchema(): array
                {
                    return $this->schema;
                }
            },
            $operatorId
        );
    }

    public function savePageAsTemplate(int $pageId, DiyTemplateInput $input, ?int $operatorId = null): DiyTemplate
    {
        $page = $this->pageService->findDetail($pageId);
        if ($page === null || $page->publishedVersion === null) {
            throw new \DomainException('请先发布页面后再保存为模板');
        }

        $schema = DiyPageSchemaVo::fromArray($page->publishedVersion->schema, $page->page_key)->toArray();

        return $this->repository->createTemplate([
            'category_id' => $input->getCategoryId(),
            'name' => trim($input->getName()),
            'page_key' => $page->page_key,
            'page_type' => $page->page_type,
            'cover' => $input->getCover(),
            'description' => $input->getDescription(),
            'schema' => $schema,
            'sort' => $input->getSort(),
            'is_enabled' => $input->isEnabled(),
        ]);
    }

    private function requireTemplate(int $id): DiyTemplate
    {
        $template = $this->repository->findDetail($id);
        if (! $template instanceof DiyTemplate) {
            throw new \DomainException('装修模板不存在');
        }

        return $template;
    }

    private function assertPageType(string $pageType): void
    {
        if (! \in_array($pageType, DiyPageStatus::pageTypes(), true)) {
            throw new \DomainException('页面类型无效');
        }
    }
}
