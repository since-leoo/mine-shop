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

namespace Plugin\Diy\Application\Api\Content;

use App\Domain\Infrastructure\Plugin\Contract\DiyPageQueryInterface;
use Plugin\Diy\Domain\Content\DiyPage\Service\DomainDiyStorefrontService;
use Plugin\Diy\Domain\Content\DiyPage\Enum\DiyPageStatus;
use Plugin\Diy\Domain\Content\DiyPage\Service\DomainDiyPageService;
use Plugin\Diy\Domain\Content\DiyPage\Service\DomainDiyPublishService;
use Plugin\Diy\Domain\Content\DiyPage\ValueObject\DiyPageSchemaVo;
use Plugin\Diy\Infrastructure\Model\Content\DiyPageVersion;

final class AppApiDiyPageQueryService implements DiyPageQueryInterface
{
    public function __construct(
        private readonly DomainDiyPageService $diyPageService,
        private readonly DomainDiyPublishService $publishService,
        private readonly DomainDiyStorefrontService $storefrontService,
    ) {}

    public function published(string $pageKey, string $pageType = DiyPageStatus::TYPE_MINIPROGRAM): ?array
    {
        $payload = $this->diyPageService->getPublished($pageKey, $pageType);
        if ($payload === null) {
            return null;
        }

        return $this->storefrontService->resolve($payload);
    }

    public function preview(string $token): ?array
    {
        $previewToken = $this->publishService->resolvePreview($token);
        if ($previewToken->version_id === null) {
            return null;
        }

        $version = $this->diyPageService->repository->findVersionWithPage($previewToken->page_id, $previewToken->version_id);
        if (! $version instanceof DiyPageVersion || $version->page === null) {
            return null;
        }

        $payload = DiyPageSchemaVo::fromArray($version->schema, $version->page->page_key)->publishedPayload();
        $payload['published_at'] = $version->published_at?->toDateTimeString();

        return $this->storefrontService->resolve($payload);
    }

}
