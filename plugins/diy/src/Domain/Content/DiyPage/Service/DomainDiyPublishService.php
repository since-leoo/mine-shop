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

namespace Plugin\Diy\Domain\Content\DiyPage\Service;

use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Permission\User;
use Carbon\Carbon;
use Plugin\Diy\Domain\Content\DiyPage\Contract\DiyPublishScheduleInput;
use Plugin\Diy\Domain\Content\DiyPage\Enum\DiyPageStatus;
use Plugin\Diy\Domain\Content\DiyPage\Repository\DiyPublishRecordRepository;
use Plugin\Diy\Infrastructure\Model\Content\DiyPagePreviewToken;
use Plugin\Diy\Infrastructure\Model\Content\DiyPagePublishRecord;
use Plugin\Diy\Infrastructure\Model\Content\DiyPageVersion;

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

        if ($operatorId === null || $operatorId <= 0) {
            throw new \DomainException('定时发布需要有效的后台操作人');
        }
        $page = $this->pageService->requireLockedPage($input->getPageId());
        $version = $this->pageService->repository->findVersion($page->id, $input->getVersionId());
        if ($version === null) {
            throw new \DomainException('页面版本不存在');
        }
        $this->pageService->validateVersion($page, $version);

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

    public function cancelSchedule(int $recordId, int $pageId): bool
    {
        $record = $this->repository->lockRecord($recordId);
        if (! $record instanceof DiyPagePublishRecord || $record->page_id !== $pageId) {
            throw new \DomainException('发布记录不存在');
        }

        if ($record->publish_status !== 'pending') {
            throw new \DomainException('只能取消待发布记录');
        }

        return $this->repository->cancelRecord($recordId);
    }

    /** @return list<int> */
    public function dueScheduleIds(): array
    {
        return $this->repository->dueScheduleIds();
    }

    public function lockDueSchedule(int $id): ?DiyPagePublishRecord
    {
        $record = $this->repository->lockRecord($id);
        if ($record === null || $record->publish_type !== 'scheduled' || $record->publish_status !== 'pending'
            || $record->scheduled_at === null || $record->scheduled_at->isFuture()) {
            return null;
        }
        return $record;
    }

    public function scheduleOperator(DiyPagePublishRecord $record): User
    {
        $operator = $this->repository->findOperator((int) $record->operator_id);
        if ($operator === null || $operator->status->isDisable()
            || (! $operator->isSuperAdmin() && ! $operator->hasPermission('mall:diy:page:publish'))) {
            throw new \DomainException('定时发布操作人已失效或没有发布权限');
        }
        return $operator;
    }

    public function publishScheduled(DiyPagePublishRecord $record): void
    {
        $page = $this->pageService->requireLockedPage($record->page_id);
        $version = $this->pageService->repository->findVersion($page->id, (int) $record->version_id);
        if ($version === null) {
            throw new \DomainException('定时发布版本不存在');
        }
        if ($version->status !== DiyPageStatus::VERSION_PUBLISHED || $page->published_version_id !== $version->id) {
            $this->pageService->publishSelectedVersion($page, $version, $record->operator_id);
        }
        $record->fill(['publish_status' => 'published', 'published_at' => Carbon::now(), 'error_message' => null])->save();
    }

    public function failSchedule(int $id, string $reason): void
    {
        $this->repository->failSchedule($id, $reason);
    }

    public function rollback(int $pageId, int $versionId, ?int $operatorId = null): DiyPageVersion
    {
        $page = $this->pageService->requireLockedPage($pageId);
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
