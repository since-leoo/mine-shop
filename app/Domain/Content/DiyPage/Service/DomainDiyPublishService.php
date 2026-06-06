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

use App\Domain\Content\DiyPage\Contract\DiyPublishScheduleInput;
use App\Domain\Content\DiyPage\Enum\DiyPageStatus;
use App\Domain\Content\DiyPage\Repository\DiyPublishRecordRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Content\DiyPage;
use App\Infrastructure\Model\Content\DiyPagePreviewToken;
use App\Infrastructure\Model\Content\DiyPagePublishRecord;
use App\Infrastructure\Model\Content\DiyPageVersion;
use Carbon\Carbon;

final class DomainDiyPublishService extends IService
{
    public function __construct(
        public readonly DiyPublishRecordRepository $repository,
        private readonly DomainDiyPageService $pageService,
    ) {}

    public function records(int $pageId): array
    {
        return $this->repository->records($pageId);
    }

    public function schedule(DiyPublishScheduleInput $input, ?int $operatorId = null): DiyPagePublishRecord
    {
        if ($input->getScheduledAt()->lessThanOrEqualTo(Carbon::now())) {
            throw new \DomainException('定时发布时间必须晚于当前时间');
        }

        if ($this->repository->hasPendingSchedule($input->getPageId())) {
            throw new \DomainException('已有待执行的定时发布');
        }

        return $this->repository->createRecord([
            'page_id' => $input->getPageId(),
            'version_id' => $input->getVersionId(),
            'publish_type' => 'scheduled',
            'publish_status' => 'pending',
            'scheduled_at' => $input->getScheduledAt(),
            'operator_id' => $operatorId,
            'remark' => $input->getRemark(),
        ]);
    }

    public function cancelSchedule(int $recordId): bool
    {
        $record = $this->repository->findRecord($recordId);
        if (! $record instanceof DiyPagePublishRecord) {
            throw new \DomainException('发布记录不存在');
        }

        if ($record->publish_status !== 'pending') {
            throw new \DomainException('只能取消待发布记录');
        }

        return $this->repository->cancelRecord($recordId);
    }

    public function rollback(int $pageId, int $versionId, ?int $operatorId = null): DiyPageVersion
    {
        $page = $this->pageService->repository->findById($pageId);
        if (! $page instanceof DiyPage) {
            throw new \DomainException('DIY页面不存在');
        }

        $version = $this->pageService->repository->findVersion($pageId, $versionId);
        if (! $version instanceof DiyPageVersion || $version->status !== DiyPageStatus::VERSION_PUBLISHED) {
            throw new \DomainException('只能回滚到历史已发布版本');
        }

        $published = $this->pageService->repository->publishVersion($page, $version, $operatorId);
        $this->repository->createRecord([
            'page_id' => $pageId,
            'version_id' => $versionId,
            'publish_type' => 'rollback',
            'publish_status' => 'published',
            'published_at' => Carbon::now(),
            'operator_id' => $operatorId,
        ]);

        return $published;
    }

    public function createPreviewToken(int $pageId, ?int $versionId, ?int $operatorId = null): DiyPagePreviewToken
    {
        return $this->repository->createPreviewToken([
            'page_id' => $pageId,
            'version_id' => $versionId,
            'token' => bin2hex(random_bytes(16)),
            'expired_at' => Carbon::now()->addMinutes(30),
            'created_by' => $operatorId,
        ]);
    }

    public function resolvePreview(string $token): DiyPagePreviewToken
    {
        $model = $this->repository->findPreviewToken($token);
        if (! $model instanceof DiyPagePreviewToken) {
            throw new \DomainException('预览令牌不存在');
        }

        if ($model->expired_at->lessThanOrEqualTo(Carbon::now())) {
            throw new \DomainException('预览令牌已过期');
        }

        return $model;
    }
}
